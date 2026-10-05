/*
 * Postman pre-request script for Addendum request signatures.
 *
 * Required collection/environment variables:
 * - access_token: RS256 JWT access token
 * - fingerprint: device fingerprint used when the token was issued
 * - request_signature_secret: value of REQUEST_SIGNATURE_SECRET
 */
(function signAddendumRequest() {
    function readVariable(name, fallback) {
        var scopes = [pm.variables, pm.environment, pm.collectionVariables, pm.globals];

        for (var i = 0; i < scopes.length; i++) {
            var scope = scopes[i];
            if (!scope || typeof scope.get !== 'function') {
                continue;
            }

            var value = scope.get(name);
            if (value !== undefined && value !== null && String(value) !== '') {
                return String(value);
            }
        }

        return fallback;
    }

    function requestTarget() {
        if (pm.request.url && typeof pm.request.url.getPathWithQuery === 'function') {
            var target = pm.request.url.getPathWithQuery();
            return target.charAt(0) === '/' ? target : '/' + target;
        }

        var path = '/';
        if (pm.request.url && Array.isArray(pm.request.url.path)) {
            path += pm.request.url.path.join('/');
        }

        var query = '';
        if (pm.request.url && pm.request.url.query && typeof pm.request.url.query.all === 'function') {
            query = pm.request.url.query.all()
                .filter(function (param) { return !param.disabled; })
                .map(function (param) {
                    return encodeURIComponent(param.key) + '=' + encodeURIComponent(param.value || '');
                })
                .join('&');
        }

        return query !== '' ? path + '?' + query : path;
    }

    function requestBody() {
        if (!pm.request.body) {
            return '';
        }

        if (pm.request.body.mode === 'raw') {
            return pm.request.body.raw || '';
        }

        return '';
    }

    function decodeJwtPayload(token) {
        var parts = token.split('.');
        if (parts.length !== 3) {
            throw new Error('access_token is not a compact JWT');
        }

        var base64 = parts[1].replace(/-/g, '+').replace(/_/g, '/');
        while (base64.length % 4 !== 0) {
            base64 += '=';
        }

        return JSON.parse(CryptoJS.enc.Utf8.stringify(CryptoJS.enc.Base64.parse(base64)));
    }

    function hmacSha256(data, key) {
        return CryptoJS.HmacSHA256(data, key).toString(CryptoJS.enc.Hex);
    }

    var accessToken = readVariable('access_token', '');
    var fingerprint = readVariable('fingerprint', 'postman-dev-fingerprint');
    var requestSignatureSecret = readVariable('request_signature_secret', '');

    if (accessToken === '') {
        throw new Error('Missing Postman variable: access_token');
    }

    if (requestSignatureSecret.length < 32) {
        throw new Error('Missing or too short Postman variable: request_signature_secret');
    }

    var payload = decodeJwtPayload(accessToken);
    if (!payload.jti) {
        throw new Error('JWT payload does not contain jti');
    }

    var timestamp = Math.floor(Date.now() / 1000).toString();
    var nonce = CryptoJS.lib.WordArray.random(16).toString(CryptoJS.enc.Hex);
    var fingerprintHash = payload.fingerprintHash || '';
    var signingKey = hmacSha256(String(payload.jti) + String(fingerprintHash), requestSignatureSecret);
    var data = timestamp + fingerprint + pm.request.method + requestTarget() + nonce + requestBody();
    var signature = hmacSha256(data, signingKey);

    pm.request.headers.upsert({ key: 'Authorization', value: 'Bearer ' + accessToken });
    pm.request.headers.upsert({ key: 'X-Request-Timestamp', value: timestamp });
    pm.request.headers.upsert({ key: 'X-Request-Fingerprint', value: fingerprint });
    pm.request.headers.upsert({ key: 'X-Request-Nonce', value: nonce });
    pm.request.headers.upsert({ key: 'X-Request-Signature', value: signature });

    pm.variables.set('jwt_jti', String(payload.jti));
    pm.variables.set('jwt_fingerprint_hash', String(fingerprintHash));
    pm.variables.set('last_request_signature', signature);
    pm.variables.set('last_request_nonce', nonce);
    pm.variables.set('last_request_timestamp', timestamp);
}());

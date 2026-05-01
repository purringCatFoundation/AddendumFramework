vcl 4.1;

import std;

backend default {
    .host = "frankenphp";
    .port = "80";
}

acl purge {
    "localhost";
    "127.0.0.1";
    "10.0.0.0"/8;
    "172.16.0.0"/12;
    "192.168.0.0"/16;
}

sub vcl_recv {
    if (req.method == "PURGE") {
        if (!client.ip ~ purge) {
            return (synth(405, "Not allowed"));
        }

        if (!req.http.X-Cache-Invalidate) {
            return (synth(400, "Missing X-Cache-Invalidate"));
        }

        set req.http.X-Invalidate-Regex = regsuball(req.http.X-Cache-Invalidate, ",", "|");
        ban("obj.http.Surrogate-Key ~ (^| )(" + req.http.X-Invalidate-Regex + ")( |$)");

        return (synth(200, "Ban added"));
    }

    if (req.method !~ "^(GET|HEAD|OPTIONS)$") {
        return (pass);
    }

    if (req.http.Authorization) {
        return (pass);
    }
}

sub vcl_backend_response {
    if (bereq.method !~ "^(GET|HEAD|OPTIONS)$") {
        set beresp.uncacheable = true;
        set beresp.ttl = 0s;
        return (deliver);
    }

    if (beresp.status >= 400 || beresp.http.Cache-Control ~ "private|no-store") {
        set beresp.uncacheable = true;
        set beresp.ttl = 0s;
        return (deliver);
    }

    if (beresp.http.Surrogate-Control ~ "max-age=") {
        set beresp.ttl = std.duration(regsub(beresp.http.Surrogate-Control, ".*max-age=([0-9]+).*", "\1") + "s", 60s);
    }
}

sub vcl_deliver {
    if (obj.hits > 0) {
        set resp.http.X-Varnish-Cache = "HIT";
    } else {
        set resp.http.X-Varnish-Cache = "MISS";
    }
}

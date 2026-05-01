#!/usr/bin/env bash
set -euo pipefail

BASE_URL="${BASE_URL:-http://localhost:8080}"
RESOURCE_URL="${BASE_URL}/dev/http-cache/articles/123"

first_headers="$(mktemp)"
first_body="$(mktemp)"
second_headers="$(mktemp)"
second_body="$(mktemp)"
patch_headers="$(mktemp)"
third_headers="$(mktemp)"
head_headers="$(mktemp)"
options_headers="$(mktemp)"

cleanup() {
    rm -f "$first_headers" "$first_body" "$second_headers" "$second_body" "$patch_headers" "$third_headers" "$head_headers" "$options_headers"
}
trap cleanup EXIT

curl -fsS -D "$first_headers" -o "$first_body" "$RESOURCE_URL" >/dev/null
grep -qi '^Cache-Control: public, max-age=60, s-maxage=60' "$first_headers"
grep -qi '^X-Http-Cache: MISS' "$first_headers"
grep -qi '^X-Http-Cache-Provider: redis' "$first_headers"
grep -qi '^X-Redis-Cache: MISS' "$first_headers"

curl -fsS -D "$second_headers" -o "$second_body" "$RESOURCE_URL" >/dev/null
grep -qi '^X-Http-Cache: HIT' "$second_headers"
grep -qi '^X-Http-Cache-Provider: redis' "$second_headers"
grep -qi '^X-Redis-Cache: HIT' "$second_headers"
cmp -s "$first_body" "$second_body"

curl -fsS -X PATCH -D "$patch_headers" -o /dev/null "$RESOURCE_URL" >/dev/null
grep -qi '^Cache-Control: private, no-store' "$patch_headers"
grep -qi '^X-Http-Cache: INVALIDATE' "$patch_headers"
grep -qi '^X-Http-Cache-Provider: redis' "$patch_headers"
grep -qi '^X-Cache-Invalidate: article:123' "$patch_headers"

curl -fsS -D "$third_headers" -o /dev/null "$RESOURCE_URL" >/dev/null
grep -qi '^X-Http-Cache: MISS' "$third_headers"
grep -qi '^X-Redis-Cache: MISS' "$third_headers"

curl -fsS -I -D "$head_headers" -o /dev/null "$RESOURCE_URL" >/dev/null
grep -qi '^Cache-Control: public, max-age=60, s-maxage=60' "$head_headers"

curl -fsS -X OPTIONS -D "$options_headers" -o /dev/null "$RESOURCE_URL" >/dev/null
grep -qi '^Cache-Control: public, max-age=60, s-maxage=60' "$options_headers"

printf 'HTTP cache smoke test passed\n'

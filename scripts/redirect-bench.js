/**
 * k6 script for short-link redirect throughput.
 *
 * maxRedirects: 0 keeps the probe on our app — we measure the 302 hop only,
 * not the destination site. Phase 4 requires this so the remote URL is never fetched.
 *
 * Usage:
 *   k6 run -e BASE_URL=http://127.0.0.1:8000 -e CODE=bench -e LABEL=before-null scripts/redirect-bench.js
 */
import http from 'k6/http';
import { check } from 'k6';

export const options = {
  scenarios: {
    redirect: {
      executor: 'constant-vus',
      vus: Number(__ENV.VUS || 20),
      duration: __ENV.DURATION || '15s',
    },
  },
  thresholds: {
    http_req_failed: ['rate<0.01'],
  },
};

const baseUrl = (__ENV.BASE_URL || 'http://127.0.0.1:8000').replace(/\/$/, '');
const code = __ENV.CODE || 'bench';
const expected = Number(__ENV.EXPECTED_STATUS || 302);

export default function () {
  const res = http.get(`${baseUrl}/${code}`, {
    redirects: 0,
    tags: { label: __ENV.LABEL || 'redirect' },
  });

  check(res, {
    'expected status': (r) => r.status === expected,
  });
}

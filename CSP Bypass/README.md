# CSP Bypass

## 개요

- CSP(Content Security Policy)는 브라우저가 실행할 수 있는 스크립트의 출처를 제한하는 XSS 방어 메커니즘입니다.
- CSP Bypass는 이 정책을 우회하여 악성 스크립트를 실행시키는 공격 기법입니다.
- CSP가 설정되어 있더라도 whitelist 구성이 잘못되거나 입력값 검증이 없을 경우 공격이 가능합니다.

---

## CSP Bypass 취약점 유형

| 구분 | 설명 | 특징 |
|------|------|------|
| Whitelist 도메인 악용 | 신뢰된 도메인에 악성 JS 업로드 | pastebin, CDN 등 공유 플랫폼 악용 |
| `'self'` 악용 | 서버에 직접 악성 JS 파일 업로드 | 동일 출처 허용 정책 우회 |
| JSONP 악용 | whitelist 도메인의 JSONP 엔드포인트 이용 | 콜백 파라미터로 스크립트 실행 |
| nonce/hash 재사용 | 고정된 nonce 또는 hash 값 재사용 | 동적 생성 미적용 시 우회 가능 |
| `'unsafe-inline'` 허용 | 인라인 스크립트 실행 허용 | CSP 설정 자체가 취약한 경우 |

---

## 핵심 개념

- CSP는 HTTP 응답 헤더에 포함되며, 브라우저가 허용된 출처의 스크립트만 실행하도록 제한합니다.
- whitelist에 사용자가 임의 콘텐츠를 업로드할 수 있는 도메인이 포함되면 CSP가 무력화됩니다.
- 입력값 검증 없이 외부 URL을 script src에 직접 삽입하면 공격자가 원하는 스크립트를 실행할 수 있습니다.

---

## 공격 방식 설명

- 공격자는 CSP whitelist에 허용된 도메인(cdn.jsdelivr.net, pastebin.com 등)에 악성 JS를 업로드합니다.
- 해당 URL을 입력창에 삽입하면 브라우저가 신뢰된 도메인으로 인식하여 스크립트를 실행합니다.
- 또는 서버에 직접 악성 JS 파일을 업로드하여 `'self'` 정책을 통해 실행시킵니다.

---

## 예시

- 정상 CSP 헤더  
  → `Content-Security-Policy: script-src 'self';`

- 취약한 CSP 헤더  
  → `Content-Security-Policy: script-src 'self' cdn.jsdelivr.net pastebin.com unpkg.com;`

- 공격 입력  
  → `https://cdn.jsdelivr.net/gh/digininja/csp_bypass/alert.js`  
  → `https://pastebin.com/raw/XXXXXX`  
  → `http://대상서버/악성파일.js`  

---

## 특징

- CSP가 존재해도 설정이 잘못되면 XSS와 동일한 피해가 발생합니다.
- 세션 쿠키 탈취 및 세션 하이재킹으로 이어질 수 있습니다.
- 피싱 사이트 유도, 악성 코드 실행 등 다양한 2차 공격이 가능합니다.
- 브라우저 콘솔에 에러가 없어 피해자가 공격 사실을 인지하기 어렵습니다.

---

## 대응 방안

- CSP whitelist를 `'self'`만으로 최소화하고, 불필요한 외부 도메인을 제거합니다.
- pastebin.com, cdn.jsdelivr.net 등 사용자 콘텐츠 업로드가 가능한 도메인은 반드시 제외합니다.
- 도메인 기반 whitelist 대신 nonce 또는 hash 기반 CSP를 적용합니다.
- 사용자 입력값에 대한 서버사이드 URL 검증을 구현합니다.
- 쿠키에 `HttpOnly`, `Secure`, `SameSite` 옵션을 설정하여 쿠키 탈취를 방지합니다.

---

## 정리

- CSP Bypass는 XSS 2차 방어선인 CSP를 우회하는 공격 기법입니다.
- CSP가 있다고 안전한 것이 아니며, whitelist 구성과 입력값 검증이 함께 적용되어야 합니다.
- nonce 기반 CSP 적용과 whitelist 최소화가 가장 효과적인 대응 방법입니다.

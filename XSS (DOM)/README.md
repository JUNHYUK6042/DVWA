# XSS (DOM)

## 개요
- DOM XSS는 서버를 거치지 않고 브라우저 내 JavaScript가 DOM을 직접 조작하는 과정에서 악성 스크립트가 실행되는 취약점입니다.
- Reflected / Stored XSS와 달리 서버 응답에 스크립트가 포함되지 않으며, 브라우저 안에서만 공격이 완결됩니다.
- 주로 URL의 `?파라미터`, `location.hash`, `document.referrer` 등  
  클라이언트 측 데이터를 JavaScript가 검증 없이 DOM에 삽입할 때 발생합니다.

---

## XSS 유형 비교
| 구분 | 설명 | 특징 |
|------|------|------|
| Reflected XSS | URL을 통해 입력값이 즉시 반사 | 링크 클릭 시 실행, 서버에 저장되지 않음 |
| Stored XSS | 입력값이 DB에 저장된 후 출력 시 실행 | 페이지 접속만으로 실행, 지속적 피해 |
| DOM-based XSS | DOM 조작을 통해 클라이언트에서 실행 | 서버를 거치지 않고 브라우저 내에서 실행 |

---

## 핵심 개념
- 서버는 정상적인 응답을 반환하지만, 브라우저의 JavaScript가 URL이나 입력값을 가져다 DOM에 직접 삽입할 때 발생합니다.
- 서버가 스크립트를 HTML에 삽입하지 않기 때문에 서버 측 필터링만으로는 방어가 불가능합니다.
- 취약점의 원인이 서버 소스코드가 아닌 클라이언트 측 JavaScript 코드에 있습니다.

---

## 주요 취약 함수
- 아래 함수 및 속성에 사용자 입력값이 그대로 전달될 경우 DOM XSS가 발생합니다.

| 취약 함수 / 속성 | 설명 |
|----------------|------|
| `innerHTML` | HTML 태그를 파싱하여 DOM에 삽입 |
| `document.write()` | 문서에 직접 HTML 삽입 |
| `eval()` | 문자열을 JavaScript로 실행 |
| `location.href` | URL을 직접 변경 |
| `setTimeout('문자열')` | 문자열을 JS로 실행 |

---

## 공격 방식 설명
- 공격자는 악성 스크립트가 포함된 URL을 피해자에게 전달합니다.
- 피해자가 URL에 접속하면 브라우저의 JavaScript가 URL 파라미터 값을 읽어 DOM에 직접 삽입합니다.
- 서버는 해당 값을 HTML에 삽입하지 않지만 클라이언트 JS가 처리하는 과정에서 스크립트가 실행됩니다.

---

## 예시
- 정상 입력
  → `http://192.168.11.36/DVWA/vulnerabilities/xss_d/?default=English`
  → 드롭다운에 English가 선택된 상태로 출력

- 공격 입력
  → `http://192.168.11.36/DVWA/vulnerabilities/xss_d/?default=<script>alert(1)</script>`
  → JS가 `default` 파라미터 값을 DOM에 삽입하면서 스크립트 실행

- 쿠키 탈취
  → `http://192.168.11.36/DVWA/vulnerabilities/xss_d/?default=<script>alert(document.cookie)</script>`

- 태그 이탈 후 이벤트 핸들러 우회
  → `http://192.168.11.36/DVWA/vulnerabilities/xss_d/?default=</option></select><img src=x onerror=alert('XSS')>`
  → `<select>` 태그 안에 값이 삽입되는 구조일 때 태그를 강제로 닫고 새로운 태그 삽입

---

## 특징
- GET 파라미터 방식의 DOM XSS는 서버 로그에 요청이 기록되지만, 서버가 스크립트를 삽입하는 것은 아닙니다.
- 서버 소스코드를 점검해도 취약점을 발견하기 어렵고, 클라이언트 JS 코드를 직접 분석해야 합니다.
- WAF나 서버 측 필터링만으로는 완전한 방어가 불가능합니다.
- 쿠키 탈취, 세션 하이재킹, 피싱 등 Reflected / Stored XSS와 동일한 피해가 발생합니다.

---

## 대응 방안
- 사용자 입력값이나 URL 값을 DOM에 삽입할 때 반드시 안전한 함수를 사용합니다.

| 취약한 방식 | 안전한 방식 |
|-----------|-----------|
| `innerHTML = 입력값` | `textContent = 입력값` |
| `document.write(입력값)` | DOM API로 요소 생성 후 삽입 |
| `eval(입력값)` | 사용 자체를 지양 |

- 클라이언트 측 JavaScript에서 URL, referrer 등 외부 데이터를 사용할 때 반드시 검증 및 인코딩을 적용합니다.
- CSP(Content Security Policy) 헤더를 설정하여 허용되지 않은 스크립트 실행을 차단합니다.
- HttpOnly 쿠키를 설정하여 스크립트를 통한 쿠키 접근을 차단합니다.

---

## 정리
- DOM XSS는 서버가 아닌 브라우저의 JavaScript가 취약점의 원인이 되는 XSS입니다.
- 서버 측 방어만으로는 막을 수 없으며, 클라이언트 JS 코드 자체의 보안이 핵심입니다.
- `innerHTML` 등 위험한 DOM 조작 함수 사용을 지양하고, 외부 입력값을 DOM에 삽입할 때 반드시 검증 및 인코딩을 적용해야 합니다.

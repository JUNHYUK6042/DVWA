# Insecure CAPTCHA

## 개요

- CAPTCHA는 사람과 봇을 구별하기 위한 자동화 공격 방어 수단입니다.
- Insecure CAPTCHA는 CAPTCHA 검증 로직이 서버사이드에서 제대로 수행되지 않아,  
  파라미터 조작만으로 CAPTCHA를 우회할 수 있는 취약점입니다.
- OWASP Top 10 2025 기준 **A06: Insecure Design**에 해당합니다.

---

## Insecure CAPTCHA 취약점 유형

| 구분 | 설명 | 특징 |
|------|------|------|
| step 파라미터 변조 | CAPTCHA 검증 단계를 건너뛰고 다음 단계로 직접 요청 | step=2로 직접 전송하면 CAPTCHA 우회 가능 |
| passed_captcha 위조 | 클라이언트가 CAPTCHA 통과 여부를 직접 전달 | passed_captcha=true를 임의로 삽입 |
| 서버 재검증 부재 | 서버가 reCAPTCHA API에 별도 검증 요청을 하지 않음 | 클라이언트 전달값을 그대로 신뢰 |

---

## 핵심 개념

- Insecure CAPTCHA의 핵심은 **프로세스 검증 누락**입니다.
- CAPTCHA 자체가 존재하더라도 서버가 검증 결과를 재확인하지 않으면 의미가 없습니다.
- `step`, `passed_captcha` 등 프로세스 흐름을 결정하는 파라미터를 클라이언트가 조작할 수 있는 구조가 근본 원인입니다.

---

## 공격 방식 설명 (CAPTCHA Bypass)
- 공격자는 Burp Suite Intercept로 요청을 가로채 `step=1`을 `step=2`로 변조합니다.
- `passed_captcha=true`를 파라미터에 추가하여 CAPTCHA를 통과한 것처럼 서버에 전송합니다.
- 서버가 클라이언트 전달값을 검증 없이 신뢰하므로, CAPTCHA를 실제로 풀지 않아도 기능이 수행됩니다.

---

## 예시
- 정상 흐름   
  → CAPTCHA 풀기 → step=1 전송 → 서버가 reCAPTCHA API로 검증 → step=2 진행 → 기능 수행  

- 취약한 구조    
  → CAPTCHA 무시 → step=2, passed_captcha=true 직접 전송 → 서버가 통과 처리 → 기능 수행  

---

## 특징
- CAPTCHA가 구현되어 있어도 서버 검증이 없으면 방어 수단으로 동작하지 않습니다.
- Burp Suite로 파라미터 한 줄만 수정하면 우회가 가능합니다.
- 비밀번호 변경, 계정 생성 등 중요 기능에 적용된 경우 피해가 큽니다.
- 자동화 공격(봇)이 CAPTCHA 없이 대량 요청을 수행할 수 있습니다.

---

## 대응 방안
- CAPTCHA 검증은 반드시 서버사이드에서 수행하고, 클라이언트가 전달한 `passed_captcha` 값을 신뢰하지 않습니다.
- 매 요청마다 서버가 reCAPTCHA API에 직접 검증 요청을 보내 결과를 확인합니다.
- `step`, `passed_captcha` 등 프로세스 흐름을 제어하는 값은 서버 세션에서 관리하고, 클라이언트 전달값을 보안 판단 기준으로 사용하지 않습니다.
- 중요 기능에는 CAPTCHA 검증 성공 여부를 세션으로 관리하여 단계 우회를 방지합니다.

---

## 정리
- Insecure CAPTCHA는 CAPTCHA가 존재하더라도 서버사이드 검증이 없으면 파라미터 조작만으로 우회 가능한 취약점입니다.
- 핵심은 **프로세스 검증 누락**이며, 서버가 reCAPTCHA API를 통해 직접 검증하고 세션 기반으로 흐름을 제어해야 합니다.

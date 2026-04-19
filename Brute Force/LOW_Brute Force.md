# Brute Force

- 본 실습은 DVWA(Damn Vulnerable Web Application) 환경에서 Brute Force 공격을 수행하고,  
이를 주요정보통신기반시설 기술적 취약점 분석·평가 기준에 따라 분석하는 것을 목적으로 합니다.

---

## 주요정보통신기반시설 기준

> 출처 :  https://www.kisa.or.kr/2060204/form?postSeq=22&page=1#fnPostAttachDownload

### 점검 내용

- 사용자 계정 로그인 실패 시 계정 잠금 임계값 설정 여부 점검

---

### 점검 목적

- 무차별 대입 공격(Brute Force Attack)에 대응하여 시스템 자원을 보호하고 비인가 접근을 차단하기 위함
- 무차별 대입 공격(Brute Force)에 대응하여 계정 탈취 방지

---

### 보안 위협

- 로그인 시도 제한이 없을 경우 공격자는 무제한 인증 시도 가능
- 비밀번호가 일치할 때까지 반복 공격 수행 가능
- 계정 탈취 및 정보 유출 가능성 증가

---

### 판단 기준

- 양호: 로그인 실패 횟수 10회 이하 제한
- 취약: 로그인 실패 횟수 제한 없음 또는 10회 초과

---

## 점검 절차

1. 로그인 기능 존재 여부 확인  
2. 동일 계정으로 반복 로그인 시도 수행  
3. 로그인 실패 횟수 증가에 따른 시스템 반응 확인  
4. 특정 횟수 초과 시 계정 잠금 여부 확인  
5. 추가 인증(CAPTCHA, MFA 등) 적용 여부 확인  

---

### 로그인 요청 캡처

- DVWA 로그인 페이지에서 Username은 `admin`, Password는 `aaaaaa` 임의의 값 입력 후 로그인 시도합니다.
- Burp Suite Proxy → HTTP History에서 요청 확인합니다.

![01](/Brute%20Force/img/01.png) 

```
GET /DVWA/vulnerabilities/brute/?username=admin&password=aaaaa&Login=Login
```

- GET 방식으로 인증 정보가 전달된 것을 알 수 있으며 Username / Password 파라미터가 존재합니다.

![02](/Brute%20Force/img/02.png)

- 해당 요청을 우클릭 후 `Send to Intruder`클릭하여 전송합니다.

---

### Intruder 설정

- 다음 화면은 Burp Suite에서 Intruder 기능 화면입니다.

![03](/Brute%20Force/img/03.png)

- password 파라미터를 공격 위치로 지정하여, Position에서 `Add` 버튼을 클릭하면  
  오른쪽에 Payloads 입력 칸이 나옵니다.

---

### Payload 설정

- Payload type: Simple list
- password.lst 파일 로드 (/usr/share/john/password.lst)

![04](/Brute%20Force/img/04.png)

- Payload Configuration에서 `/usr/share/john/password.lst` 파일을 업로드합니다.

---

### 공격 수행

- Intruder 화면에서 Start Attack 버튼을 클릭하여 공격을 수행합니다.

![05](/Brute%20Force/img/05.png)

- 대부분 동일한 응답이 나오며, 특정 요청에서 응답 길이 변화 발생합니다.

---

### 응답 분석

- 응답 길이 및 내용을 비교합니다.

![06](/Brute%20Force/img/06.png)

- 대부분의 응답 길이가 동일하게 나타났지만, 특정 payload에서만 응답 길이가 다른 것을 확인할 수 있습니다.

- 응답 길이가 다른 payload를 확인한 결과, 해당 값이 정상 로그인과 관련된 것으로 추정됩니다.

---

### 공격 성공 확인

- 로그인 화면에서 `admin`/`password` 값을 입력하여 로그인 시도 합니다.

![07](/Brute%20Force/img/07.png)

#### 결과
```
"Welcome to the password protected area admin"
```

- Payload에 나온 값으로 정상 로그인에 성공하면서 응답 내용이 달라진 것을 확인했습니다.

- 이를 통해 관리자 계정(admin)의 비밀번호가 `password`임을 확인할 수 있습니다.

---

### 취약점 분석

- 실습을 진행하면서 확인해보니, 해당 시스템은 로그인 실패 횟수에 대한 제한이나  
  계정 잠금 정책이 적용되어 있지 않았습니다.

- Burp Suite Intruder를 이용해 동일한 계정(admin)에 대해 여러 비밀번호를 계속 시도했지만  
  중간에 차단되거나 제한되는 동작은 확인되지 않았습니다.

- 또한 CAPTCHA나 추가 인증(MFA), 요청 제한(Rate Limiting)과 같은 방어 기능도 존재하지 않아  
  자동화 도구를 이용한 Brute Force 공격이 가능한 환경이었습니다.

- 이러한 점을 종합해보면, 주요정보통신기반시설 U-03 기준에서 요구하는  
  계정 잠금 임계값 설정이 적용되지 않은 상태로 판단할 수 있으며,  
  실제 환경이었다면 계정 탈취로 이어질 가능성이 높습니다.

- 따라서 본 시스템은 계정 보호 정책이 미흡한 상태로, 보안 강화가 필요한 것으로 판단됩니다.

---

## 대응 방안

### 계정 잠금 정책 적용

- 로그인 실패 5~10회 제한합니다.
- 일정 횟수 초과 시 계정 잠금 적용합니다.

#### 핵심 효과

- 무차별 대입 공격을 차단합니다.

---

### Rate Limiting 적용

- 동일 IP 또는 계정에 대한 요청 횟수 제한
- 일정 시간 내 반복 요청 차단

#### 핵심 효과

- 자동화 공격(Burp Intruder 등) 방어합니다.

---

### CAPTCHA 적용

- 로그인 시 일정 횟수 실패 시 CAPTCHA 적용하여
  자동화 도구의 반복 요청을 차단합니다.

#### 핵심 효과

- 봇 공격 차단

---

### 다중 인증(MFA) 적용

- ID/PW 외 OTP, SMS 인증, 이메일 인증 등 추가 인증 수단 도입합니다.

#### 핵심 효과
- 비밀번호가 노출되더라도 계정 탈취를 방지할 수 있습니다.

---

### 비밀번호 정책 강화

- 최소 길이 및 복잡도 설정합니다.
- 8자 이상, 영문 + 숫자 + 특수문자 포함합니다.

#### 핵심 효과
- Brute Force 성공 확률 감소합니다.

---

### 로그인 실패 로그 및 탐지

- 로그인 실패 기록을 저장합니다.
- 동일 IP 반복 실패 탐지하도록 이상 로그인 시도 탐지 및 알림을 전송합니다.
- 관리자 알림 시스템을 연동합니다.

#### 핵심 효과
- 공격 조기 탐지 및 대응이 가능합니다.

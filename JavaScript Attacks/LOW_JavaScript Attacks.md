# JavaScript Attacks 취약점 실습

## 개요
- 본 실습에서는 DVWA의 Low 단계에서 JavaScript Attacks 취약점을 대상으로  
  클라이언트 JavaScript에 노출된 token 생성 로직을 분석하여 Burp Suite로 요청을 변조하고,  
  서버 측 검증을 우회할 수 있는지 확인하여 클라이언트 측 보안 로직 노출 취약점 존재 여부를 점검했습니다.

- JavaScript Attacks는 서버사이드에서 수행해야 할 보안 로직이 클라이언트 JavaScript에 노출되어  
  공격자가 이를 분석하고 올바른 값을 위조하여 서버 검증을 우회하는 취약점으로,  
  CWE-602(Client-Side Enforcement of Server-Side Security)에 해당하며  
  주요정보통신기반시설 기술적 취약점 분석·평가 방법 상세가이드의 프로세스 검증 누락(p.739) 항목과 연계하여 점검하였습니다.

---

## 주요정보통신기반시설 기준

> 출처 : https://www.kisa.or.kr/2060204/form?postSeq=22&page=1#fnPostAttachDownload ( p.739~743 )

### 점검 내용
- 서비스 제공에 필요한 사용자 입력 및 실행단계의 흐름에 대한 검증의 적절성 여부를 점검합니다.

---

### 점검 목적
- 서버사이드에서 적절한 검증을 통해 비정상적인 입력 및 클라이언트 조작으로 인한 보안 우회를 차단하기 위함입니다.

---

### 보안 위협
- 보안 검증 로직이 클라이언트 JavaScript에 노출될 경우,  
  공격자가 이를 분석하여 올바른 token 값을 직접 계산하고 요청을 변조하여 서버 검증을 우회할 수 있습니다.
- 서버가 클라이언트에서 전달된 값을 재검증하지 않을 경우, 가격 조작·권한 우회·인증 우회 등 심각한 피해로 이어질 수 있습니다.

---

### 판단 기준

#### 양호
- 보안 검증 로직이 서버사이드에서 구현되어 있으며, 클라이언트에서 전달된 token 및 파라미터 값을 서버에서 재검증하는 경우

#### 취약
- 보안 검증 로직이 클라이언트 JavaScript에 노출되어 있으며, 서버가 클라이언트 전달값을 재검증 없이 신뢰하는 경우

---

## 점검 절차

1. DVWA JavaScript Attacks 초기 화면 및 동작 확인
2. 기본값(ChangeMe) 제출 → 실패 메시지 확인
3. Burp Suite HTTP history에서 전송된 파라미터 확인
4. 페이지 소스(Ctrl+U)에서 클라이언트 JavaScript 분석
5. CyberChef로 `md5(rot13("success"))` 계산하여 올바른 token 값 도출
6. Burp Suite Intercept로 token 및 phrase 값 변조 후 Forward
7. "Well done!" 성공 확인

---

## 취약점 검증 및 공격 수행

### 실습 화면

- DVWA의 JavaScript Attacks 페이지로, "Submit the word 'success' to win."이라는 안내 문구와 함께 기본값 `ChangeMe`가 입력된 상태입니다.
- 페이지 로드 시 클라이언트 JavaScript가 자동으로 `token = md5(rot13("ChangeMe"))` 를 계산하여 hidden 필드에 저장합니다.

<img src="/JavaScript%20Attacks/img/01.png" width="100%">

---

### ChangeMe 제출 → 실패 확인

- 기본값 `ChangeMe`를 그대로 Submit하면 서버가 "You got the phrase wrong." 메시지를 반환합니다.
- 이는 phrase 값이 "success"가 아니므로 서버 검증에서 실패한 결과입니다.

<img src="/JavaScript%20Attacks/img/02.png" width="100%">

---

### Burp Suite HTTP history 확인

- Burp Suite HTTP history에서 Submit 시 전송된 POST 요청에서 아래 파라미터가 전송된 것을 확인했습니다.

```
token=8b479aefbd90795395b3e7089ae0dc09&phrase=ChangeMe&send=Submit
```

- `token` 값은 JavaScript가 자동 계산한 `md5(rot13("ChangeMe"))`의 결과이며, `phrase`는 ChangeMe 그대로 전송되었습니다.

<img src="/JavaScript%20Attacks/img/03.png" width="100%">

---

### 페이지 소스에서 JavaScript 분석

- 페이지 소스(Ctrl+U)를 확인한 결과, MD5 라이브러리 코드가 그대로 노출되어 있으며 하단에서 token 생성 로직을 확인할 수 있습니다.
- 이 코드를 분석한 이유는 서버가 token을 어떻게 검증하는지, 그 기준을 클라이언트 소스에서 직접 파악할 수 있기 때문입니다.

```javascript
function rot13(inp) {
    return inp.replace(/[a-zA-Z]/g,function(c){
        return String.fromCharCode((c<="Z"?90:122)>=(c=c.charCodeAt(0)+13)?c:c-26);
    });
}

function generate_token() {
    var phrase = document.getElementById("phrase").value;
    document.getElementById("token").value = md5(rot13(phrase));
}

generate_token();
```

- **`function rot13(inp) {...}`** — 서버가 token 생성에 사용하는 ROT13 알고리즘이 클라이언트 JavaScript에 그대로 노출되어 있습니다.  
- 공격자는 이 함수를 읽는 것만으로 변환 방식을 완전히 파악할 수 있습니다.

- **`document.getElementById("token").value = md5(rot13(phrase))`** — 이 줄이 핵심 취약점입니다.  
  서버가 검증에 사용하는 token 값을 클라이언트가 직접 계산하여 hidden 필드에 넣는 구조로,  
  알고리즘(`md5(rot13(...))`)이 완전히 노출됩니다.

- 공격자는 이 공식을 그대로 사용해 원하는 phrase에 대한 올바른 token을 직접 계산할 수 있습니다.

- **`generate_token()`** — 페이지 로드 시 자동 실행되어 token이 클라이언트에서 생성되며  
  서버가 이 token을 검증 없이 신뢰하는 구조이므로, 공격자가 계산한 임의의 token으로 대체해 전송하면 서버를 속일 수 있습니다.

<img src="/JavaScript%20Attacks/img/04.png" width="100%">

---

### CyberChef로 올바른 token 값 계산

- 분석한 로직을 기반으로 CyberChef에서  
  `success`를 입력값으로 하여 ROT13 → MD5 순서로 연산을 적용했습니다.

```
rot13("success") = "fhpprff"
md5("fhpprff")  = 38581812b435834ebf84ebcc2c6424d0
```

- Output 값 `38581812b435834ebf84ebcc2c6424d0`이 서버가 기대하는 올바른 token 값임을 확인했습니다.

<img src="/JavaScript%20Attacks/img/05.png" width="100%">

---

### Burp Suite Intercept — 변조 전 요청 확인

- Burp Suite Intercept ON 상태에서 Submit을 클릭하면 요청이 가로채집니다.
- phrase가 `success`로, token은 아직 변조되지 않은 `8b479aefbd90795395b3e7089ae0dc09` 상태인걸 알 수 있습니다.

```
token=8b479aefbd90795395b3e7089ae0dc09&phrase=success&send=Submit
```

<img src="/JavaScript%20Attacks/img/06.png" width="100%">

---

### Burp Suite Intercept — token 변조 후 Forward

- 가로챈 요청에서 token 값을 CyberChef로 계산한 올바른 값으로 변조한 후 Forward 했습니다.

```
token=38581812b435834ebf84ebcc2c6424d0&phrase=success&send=Submit
```

- phrase는 `success`, token은 `md5(rot13("success"))` 결과값으로 정확히 설정되었습니다.

<img src="/JavaScript%20Attacks/img/07.png" width="100%">

---

### 공격 성공 확인 — "Well done!"

- Burp Suite Intercept를 통해 phrase와 token 값을 변조하여 서버가 "Well done!"을 반환하는 것을 확인했습니다.
- 서버가 변조된 token을 올바른 값으로 인식하여 검증을 통과시킨 것입니다.

<img src="/JavaScript%20Attacks/img/08.png" width="100%">

---

## 취약점 분석

- 클라이언트 JavaScript에 token 생성 로직 `md5(rot13(phrase))`이 그대로 노출되어, 공격자가 이를 분석하고 올바른 token을 직접 계산할 수 있었습니다.
- 서버가 token 값을 검증할 때 클라이언트에서 전달된 값을 재검증 없이 신뢰하는 구조로, 요청 변조를 통한 우회가 가능했습니다.
- Burp Suite Intercept를 통해 phrase와 token 값을 변조하여 서버가 "Well done!"을 반환하는 것을 확인하였습니다.
- **판단 : 취약** — 실제 환경이었다면 가격 조작, isAdmin 필드 변조, 인증 우회 등 심각한 피해로 이어질 수 있는 위험한 상태입니다.

---

## 대응 방안

### 서버사이드 세션 기반 token 검증 구현

- 클라이언트 JavaScript가 `md5(rot13(phrase))`를 계산하여  
  token을 생성하는 구조를 제거하고, 서버가 랜덤 token을 생성하여 세션에 저장합니다.
- 서버사이드에서 구현된 토큰 기반 접근 통제를 적용하고, 제출 시 서버 세션에 저장된 값과 비교하여 검증합니다.

```php
  session_start();
  
  // 서버가 token 생성 후 세션에 저장
  $_SESSION['token'] = bin2hex(random_bytes(32));
  echo '<input type="hidden" name="token" value="' . $_SESSION['token'] . '">';
  
  // 제출 시 서버 세션값과 비교하여 검증
  if (isset($_POST['token'], $_POST['phrase'])
      && $_POST['token'] === $_SESSION['token']
      && $_POST['phrase'] === 'success') {
      $_SESSION['token'] = bin2hex(random_bytes(32)); // 일회성 재발급
      echo 'Well done!';
  } else {
      echo 'You got the phrase wrong.';
  }
```

#### 핵심 효과

- Burp Suite로 token 값을 임의로 변조하더라도 서버 세션에 저장된 값과 달라 검증에 실패하므로 프로세스 우회가 불가능합니다.

---

### 클라이언트 JavaScript 보안 로직 의존 금지
 
- Javascript 로직 변조를 통한 논리 오류가 발생하지 않도록  
  `rot13`, `md5` 등 보안 판단에 사용되는 알고리즘을 클라이언트 JavaScript에 포함하지 않습니다.
- 페이지 소스(Ctrl+U), 개발자 도구(F12)로 누구나 열람 가능한  
  클라이언트 코드에는 검증 로직을 두지 않고, 반드시 서버사이드에서 수행합니다.

#### 핵심 효과

- 공격자가 소스코드를 분석하더라도 token 생성 알고리즘을 파악할 수 없어 올바른 token 위조가 불가능합니다.

---

### 클라이언트 전달값을 보안 판단 기준으로 사용 금지

- hidden 필드로 전달되는 token 등 클라이언트가  
  Burp Suite로 자유롭게 변조할 수 있는 값을 서버의 보안 판단 기준으로 사용하지 않습니다.
- 프로세스 검증에 필요한 값은 반드시 서버 세션에서 직접 조회하여 사용합니다.

#### 핵심 효과

- 패킷 변조 도구로 token 값을 수정하더라도 서버 세션과 일치하지 않으므로 프로세스 우회가 불가능합니다.

---

### 비정상 요청 탐지 및 로깅

- 서버사이드에서 token 또는 phrase 검증에 실패한 요청을 로그로 기록하여 반복적인 프로세스 우회 시도를 탐지합니다.
- IP, 전달된 token·phrase 값, 시각 등을 로그에 포함하여 이상 패턴을 조기에 식별합니다.

#### 핵심 효과

- 공격 시도를 조기에 탐지하고 반복 요청 발생 시 IP 차단 등 추가 대응이 가능합니다.

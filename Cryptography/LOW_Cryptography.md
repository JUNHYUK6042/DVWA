# Cryptography 취약점

## 개요

- 본 실습에서는 Cryptography 취약점을 대상으로 XOR 암호화를 사용하는 메시지 교환 시스템에서 암호화 키와 패스워드가  
  소스코드에 하드코딩되어 있어 암호문 복호화 및 로그인이 가능한지 확인하여 취약점 존재 여부를 점검했습니다.

---

## 점검 절차

1. DVWA Cryptography 페이지 접속 및 구조 확인
2. 제공된 암호문을 Decode 기능으로 복호화
3. 복호화된 패스워드로 Login 시도
4. Encode 기능을 통한 암호화 동작 확인
5. CyberChef를 통한 XOR 키 분석 및 키 복원 확인

---

## 취약점 검증 및 공격 수행

### 실습 화면

- DVWA의 Cryptography 페이지이며, 메시지를 암호화(Encode)하거나 복호화(Decode)할 수 있는 폼과  
  가로챈 암호문(`Lg4WGlQZChhSFBYSEB8bBQtPGxdNQSwEHREOAQY=`)이 제공됩니다.

![01](/Cryptography/img/01.png)

---

### Decode 기능을 이용한 암호문 복호화

- 제공된 암호문을 서버의 Decode 기능에 입력하여 복호화를 시도했습니다.

```
Message : Lg4WGlQZChhSFBYSEB8bBQtPGxdNQSwEHREOAQY=
```

![02](/Cryptography/img/02.png)

- 복호화 결과 `Your new password is: Olifant`가 출력되었습니다.
- 서버가 복호화 기능을 외부에 그대로 노출하고 있어, 공격자가 가로챈 암호문을 직접 복호화하는 것이 가능합니다.

---

### 패스워드 획득 및 로그인 시도

- 복호화 결과에서 패스워드 `Olifant`를 확인하고 Login 폼에 입력했습니다.

![03](/Cryptography/img/03.png)

---

### 로그인 성공 확인

![04](/Cryptography/img/04.png)

- Login 클릭 후 `Welcome back user` 메시지가 출력되어 로그인에 성공했습니다.
- 이를 통해 소스코드에 하드코딩된 패스워드를 복호화 기능만으로 획득하여 인증을 우회할 수 있음을 확인했습니다.

---

### Encode 기능을 통한 암호화 동작 확인

- 암호화 방식의 동작을 파악하고 CyberChef 분석에 활용하기 위해 평문을 Encode하여 결과를 확인했습니다.

```
Message : cryptocat is the best!
```

![05](/Cryptography/img/05.png)

- Encode 결과로 `FBMaGAAYDA4GRB4SQxwcEk8NFxcDQA==`가 출력되었습니다.
- Base64 형태의 문자열이 반환되며, XOR 암호화 후 Base64 인코딩이 적용된 구조임을 확인했습니다.

---

### CyberChef Base64 디코딩

- 알려진 평문 공격(Known Plaintext Attack)을 수행하기 위해 암호문을 CyberChef에서 Base64 디코딩했습니다.

![06](/Cryptography/img/06.png)

- Base64 디코딩 결과 암호화된 바이트 데이터가 확인되었습니다.

---

### More Information 확인

- CyberChef 분석이 명확하지 않아 DVWA 페이지 하단의 More Information 링크를 확인했습니다.
- XOR Encryption Algorithm, XOR Cipher 등의 링크가 제공되어 해당 시스템이 XOR 방식을 사용하고 있음을 확인했습니다.

![07](/Cryptography/img/07.png)

---

### XOR 키 패턴 분석

- From Base64 → XOR 연산 시 알려진 평문 일부를 키로 입력하자 반복되는 패턴 `wachtwoordwachtwoordwa`가 출력되었습니다.

![08](/Cryptography/img/08.png)

- XOR 암호화는 동일한 키를 반복 적용하기 때문에, 출력에서 반복 패턴을 통해 키 `wachtwoord`를 복원할 수 있습니다.

---

### 키 복원 및 복호화 확인

- 복원된 키 `wachtwoord`를 적용하여 암호문을 복호화한 결과 원본 평문 `cryptocat is the best!`가 정확히 출력되었습니다.

![09](/Cryptography/img/09.png)

- 키 복원이 성공하여 해당 키로 암호화된 모든 메시지를 복호화할 수 있는 상태임을 확인했습니다.

---

## 취약점 분석

- 암호화 키와 패스워드가 소스코드에 하드코딩되어 있으며, 서버가 복호화 기능을 외부에 그대로 노출하고 있습니다.
- 소스코드를 보면 XOR 키가 코드 내에 고정되어 있고, 패스워드 검증도 하드코딩된 문자열과의 단순 비교로 처리됩니다.

```php
$key = "wachtwoord";
// 암호화 키가 소스코드에 하드코딩 — 소스 노출 시 모든 암호문 복호화 가능
```

```php
$encoded = xor_this(base64_decode($message), $key);
// 복호화 기능을 외부에 그대로 노출 — 공격자가 가로챈 암호문을 서버를 통해 직접 복호화 가능
```

```php
if ($password == "Olifant") {
// 패스워드가 소스코드에 하드코딩 — 소스 노출 시 패스워드 즉시 획득 가능
```

- XOR 암호화는 동일한 키를 반복 사용하기 때문에 알려진 평문 공격으로 키 복원이 가능합니다.
- 복호화 기능이 외부에 노출되어 있어 서버 자체가 복호화 오라클로 악용되었습니다.

**판단 : 취약** — 실제 환경이었다면 암호화된 통신 내용 전체가 노출되고 인증 우회로 이어질 수 있습니다.

---

## 대응 방안

### 강력한 암호화 알고리즘 사용

- XOR과 같은 취약한 암호화 방식 대신 검증된 표준 암호화 알고리즘을 적용합니다.

```php
function encryptMessage($plaintext, $key) {
    $iv = random_bytes(12);
    // random_bytes()로 매 암호화마다 다른 IV 생성 — 동일 평문도 다른 암호문으로 출력
    $ciphertext = openssl_encrypt($plaintext, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag);
    // AES-256-GCM으로 암호화 — XOR 대비 수학적으로 안전한 알고리즘, 기밀성과 무결성 동시 보장
    return base64_encode($iv . $tag . $ciphertext);
    // IV + 인증태그 + 암호문을 함께 저장 — 복호화 시 무결성 검증 가능
}
```

#### 핵심 효과

- 알려진 평문 공격으로 키를 복원하는 것이 불가능합니다.

---

### 암호화 키 환경변수로 관리

- 암호화 키를 소스코드에 하드코딩하지 않고 환경변수 또는 별도의 키 관리 시스템에서 불러옵니다.

```php
$key = getenv('ENCRYPT_KEY');
// 환경변수에서 키를 불러옴 — 소스코드가 노출되어도 키 값은 알 수 없음
if (!$key || strlen($key) < 32) {
    throw new Exception('Invalid encryption key');
    // 키 길이 검증 — 최소 32바이트(256비트) 이상의 키 사용 강제
}
```

#### 핵심 효과

- 소스코드가 유출되어도 암호화 키가 노출되지 않습니다.

---

### 복호화 기능 접근 제한

- 외부에 노출된 복호화 엔드포인트에 인증 및 권한 검증을 적용하여 무분별한 복호화 요청을 차단합니다.

```php
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    // 권한 없는 요청에 403 반환 — 복호화 오라클로 악용 차단
    exit('Access denied');
    // 관리자 권한 확인 — 인가된 사용자만 복호화 기능 접근 허용
}
```

#### 핵심 효과

- 공격자가 서버를 복호화 오라클로 악용하는 것을 차단합니다.

---

### 패스워드 안전한 해시 처리

- 패스워드를 소스코드에 하드코딩하지 않고 안전한 해시 함수로 저장 및 검증합니다.

```php
$hashedPassword = password_hash($password, PASSWORD_BCRYPT);
// password_hash()로 bcrypt 해시 생성 — 패스워드 원문을 저장하지 않음
if (password_verify($inputPassword, $hashedPassword)) {
    // password_verify()로 검증 — 타이밍 공격 방지 및 해시 비교로 패스워드 원문 노출 차단
    $success = "Welcome back user";
}
```

#### 핵심 효과

- 소스코드가 유출되어도 패스워드 원문을 알 수 없습니다.

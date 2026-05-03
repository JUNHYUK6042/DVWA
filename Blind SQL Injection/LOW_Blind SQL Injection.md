# Blind SQL Injection 취약점 실습

## 개요

- 본 실습에서는 DVWA의 Low 단계에서 Blind SQL Injection 취약점을 대상으로 응답 결과가  
  직접 출력되지 않는 상황에서 조건에 따른 응답 차이와 시간 지연을 확인하여 Blind SQL Injection 취약점 존재 여부를 점검했습니다.

---

## 주요정보통신기반시설 기준

> 출처 : https://www.kisa.or.kr/2060204/form?postSeq=22&page=1#fnPostAttachDownload ( p.693 ~ 699 )

### 점검 내용

- 사용자 입력값이 SQL Query에 직접 포함되어 비정상적인 Query문이 실행되는지 점검합니다.
- 입력값 조작을 통해 데이터베이스에 비인가 접근이 가능한지 확인합니다.
- 결과가 직접 출력되지 않는 환경에서도 응답 차이 또는 시간 지연을 통해 정보 추론이 가능한지 확인합니다.

---

### 점검 목적

- 입력값 조작을 통한 비정상적인 SQL문 실행을 방지하기 위함입니다.
- 중요 정보 유출과 관리자 권한 탈취를 예방하기 위함입니다.
- SQL Injection 공격으로 인한 데이터 조회, 수정, 삭제 가능성을 차단하기 위함입니다.

---

### 보안 위협

- 사용자 입력값이 SQL 쿼리에 그대로 삽입될 경우, 공격자가 의도하지 않은 SQL문을 실행할 수 있습니다.
- 이로 인해 데이터베이스에 비인가 접근이 가능해지고 데이터의 조회, 수정, 삭제와 같은 행위로 이어질 수 있습니다.
- Blind SQL Injection은 결과가 직접 출력되지 않아도 응답 차이 또는 응답 시간을 이용해 데이터를 추론할 수 있습니다.
- 실제 환경에서는 탐지가 어렵고 반복적인 요청을 통해 계정 정보, 테이블 구조, 비밀번호 해시 등이 노출될 수 있습니다.

---

### 판단 기준

#### 양호

- 사용자 입력값 검증이 적용된 경우
- Prepared Statement를 사용한 경우
- SQL 오류 및 내부 정보가 외부에 노출되지 않는 경우
- 조건에 따른 응답 차이나 시간 지연이 발생하지 않는 경우
- DB 계정에 최소 권한만 부여된 경우

#### 취약

- 사용자 입력값이 SQL문에 직접 연결되는 경우
- 참/거짓 조건에 따라 응답 결과가 달라지는 경우
- SLEEP 함수 등을 이용했을 때 응답 시간이 지연되는 경우
- sqlmap 등의 자동화 도구를 통해 DB 정보 추출이 가능한 경우
- 데이터베이스 구조 및 계정 정보 확인이 가능한 경우

---

## 점검 절차

1. SQL Injection Blind 실습 화면 확인
2. 정상 입력값 동작 확인
3. 참 조건과 거짓 조건 입력 후 응답 차이 확인
4. 시간 지연 구문을 이용한 Time-based Blind SQL Injection 확인
5. Burp Suite 또는 브라우저에서 요청 구조 확인
6. sqlmap을 이용한 취약점 자동 탐지
7. DB / Table / Column 정보 조회
8. 계정 및 Password Hash 추출 확인
9. Blind SQL Injection 취약점 존재 여부 판단

---

## 취약점 검증 및 공격 수행

### 정상 입력 확인

- 먼저 정상적인 값인 1을 입력하여 동작을 확인했습니다.

![01](/Blind%20SQL%20Injection/img/01.png)

- 결과로 User ID exists in the database 라는 메시지가 출력되었습니다.
- 이는 User ID 1번 값이 데이터베이스에 존재한다는 의미입니다.

---

### 존재하지 않는 값 입력 확인

- 다음으로 존재하지 않는 값인 6을 입력하여 응답 차이를 확인했습니다.

![02](/Blind%20SQL%20Injection/img/02.png)

- 결과로 User ID is MISSING from the database 라는 메시지가 출력되었습니다.
- 이를 통해 입력값에 따라 화면에 출력되는 메시지가 달라지는 것을 확인할 수 있습니다.

---

### Boolean 기반 Blind SQL Injection 확인

- 참 조건과 거짓 조건을 입력하여 응답 차이가 발생하는지 확인했습니다.
```sql
  1' AND 1=1 #
```

![03](/Blind%20SQL%20Injection/img/03.png)

- 1=1 조건은 항상 참이기 때문에 User ID exists in the database 메시지가 출력되었습니다.

---

- 다음으로 1=2 조건은 항상 거짓이기 때문에 User ID is MISSING from the database 메시지가 출력됩니다.
```sql
  1' AND 1=2 #
```
- 두 요청은 같은 User ID 값을 기준으로 테스트했지만, 조건에 따라 응답 메시지가 다릅니다.
- 이를 통해 결과 데이터가 직접 출력되지 않더라도 참/거짓 응답 차이를 이용해 데이터 추론이 가능하다는 것을 확인할 수 있습니다.

---

### Time-based Blind SQL Injection 확인

- 시간 지연을 이용한 Blind SQL Injection 가능 여부를 확인하기 위해 SLEEP 함수를 사용했습니다.
```sql
  1' AND SLEEP(5) #
```

![04](/Blind%20SQL%20Injection/img/04.png)

- 해당 값을 입력했을 때 응답이 약 5초 정도 지연되는 것을 확인했습니다.
- 이는 입력한 SQL 구문이 실제 데이터베이스에서 실행되었다는 의미입니다.

---

- 다음으로 조건이 맞지 않는 값에 시간 지연 구문을 적용했습니다.
```sql
  6' AND SLEEP(5) #
```

![05](/Blind%20SQL%20Injection/img/05.png)

- 존재하지 않는 User ID에 대해서는 시간 지연이 발생하지 않고 빠르게 응답하는 것을 확인했습니다.
- 이를 통해 조건이 참일 때만 지연이 발생하는 Time-based Blind SQL Injection이 가능하다는 것을 확인했습니다.

---

## sqlmap을 이용한 취약점 탐지

- 수동으로 Boolean 기반과 Time-based 기반 취약점을 확인한 후, sqlmap을 이용해 자동화 탐지를 진행했습니다.
```
sqlmap -u "http://192.168.11.36/DVWA/vulnerabilities/sqli_blind/?id=1&Submit=Submit" --cookie="security=low; PHPSESSID=세션값"
```

![06](/Blind%20SQL%20Injection/img/06.png)

![07](/Blind%20SQL%20Injection/img/07.png)

- sqlmap 실행 결과 id 파라미터가 SQL Injection에 취약한 것으로 탐지되었습니다.
- 탐지 결과 Boolean-based Blind와 Time-based Blind 방식이 가능한 것으로 확인되었습니다.
- 백엔드 DBMS는 MySQL(MariaDB)로 식별되었습니다.
- 데이터베이스 dvwa 및 내부 테이블(users 등) 구조를 확인할 수 있었습니다.
- users 테이블에서 계정 및 비밀번호(hash 값) 추출이 가능한 것으로 확인되었습니다.
- Blind 환경에서도 응답 차이 및 시간 지연을 이용하여 데이터 탈취가 가능함을 확인했습니다.

---

### Database 정보 확인

- sqlmap을 이용하여 현재 접근 가능한 Database 정보를 확인했습니다.
```
sqlmap -u "http://192.168.11.36/DVWA/vulnerabilities/sqli_blind/?id=1&Submit=Submit" --cookie="security=low; PHPSESSID=세션값" --dbs
```

![08](/Blind%20SQL%20Injection/img/08.png)

- 실행 결과 dvwa와 information_schema 데이터베이스가 확인되었습니다.
- 이를 통해 Blind SQL Injection 환경에서도 Database 이름을 추출할 수 있다는 것을 확인했습니다.

---

### Table 정보 확인

- 확인된 dvwa Database 내부에 어떤 테이블이 존재하는지 확인했습니다.
```
sqlmap -u "http://192.168.11.36/DVWA/vulnerabilities/sqli_blind/?id=1&Submit=Submit" --cookie="security=low; PHPSESSID=세션값" -D dvwa --tables
```

![09](/Blind%20SQL%20Injection/img/09.png)

- 실행 결과 users, guestbook, access_log, security_log 등의 테이블이 확인되었습니다.
- 이 중 users 테이블은 사용자 계정 정보가 저장되어 있을 가능성이 높기 때문에 주요 확인 대상으로 판단했습니다.

---

### Column 정보 확인

- users 테이블 내부에 어떤 컬럼이 존재하는지 확인했습니다.
```
sqlmap -u "http://192.168.11.36/DVWA/vulnerabilities/sqli_blind/?id=1&Submit=Submit" --cookie="security=low; PHPSESSID=세션값" -D dvwa -T users --columns
```

![10](/Blind%20SQL%20Injection/img/10.png)

- 실행 결과 user, password, first_name, last_name, user_id 등의 컬럼을 확인할 수 있었습니다.
- 특히 user와 password 컬럼은 계정 정보와 비밀번호 해시가 저장되는 핵심 컬럼으로 확인되었습니다.

---

### 계정 정보 조회

- 확인한 users 테이블을 대상으로 실제 계정 정보가 추출 가능한지 확인했습니다.
```
sqlmap -u "http://192.168.11.36/DVWA/vulnerabilities/sqli_blind/?id=1&Submit=Submit" --cookie="security=low; PHPSESSID=세션값" -D dvwa -T users --dump
```

![11](/Blind%20SQL%20Injection/img/11.png)

- 실행 결과 admin, gordonb, 1337, pablo, smithy 등의 사용자 계정 정보가 출력되었습니다.
- 함께 출력된 값은 평문 비밀번호가 아닌 해시 형태의 Password 값입니다.
- 이를 통해 결과가 직접 출력되지 않는 Blind 환경에서도 자동화 도구를 이용하면 계정 정보 추출이 가능하다는 것을 확인했습니다.

---

## 취약점 분석

- 실습을 진행하면서 User ID 입력값이 별도의 검증 없이 SQL Query문에 그대로 삽입되는 것을 확인했습니다.

- 일반 SQL Injection처럼 데이터가 화면에 바로 출력되지는 않았지만,  
  참 조건과 거짓 조건에 따라 응답 메시지가 달라지는 것을 확인했습니다.

- 1' AND 1=1 # 입력 시에는 User ID가 존재한다는 메시지가 출력되었고,  
  1' AND 1=2 # 입력 시에는 User ID가 존재하지 않는다는 메시지가 출력되는 것을 확인할 수 있습니다.

- 또한 SLEEP 함수를 이용했을 때 조건에 따라 응답 시간이 지연되는 것을 확인했습니다.

- 이를 통해 해당 시스템은 Boolean-based Blind SQL Injection과 Time-based Blind SQL Injection 모두 가능한 상태라고 판단했습니다.

- 이후 sqlmap을 사용하여 Database, Table, Column 정보를 확인할 수 있었고,  
  최종적으로 users 테이블의 계정 정보와 Password Hash 값까지 추출할 수 있었습니다.

- 따라서 해당 시스템은 Blind SQL Injection 취약점이 존재하며,  
  실제 환경이었다면 관리자 계정 탈취, 개인정보 유출, 데이터 변조 등으로 이어질 수 있는 위험한 상태로 판단됩니다.

---

## 대응 방안

### Prepared Statement 사용

- 사용자 입력값을 SQL Query에 직접 연결하지 않고,  
  Prepared Statement를 사용하여 SQL문과 입력값을 분리합니다.

#### 핵심 효과

- 입력값이 SQL 문법으로 해석되는 것을 막을 수 있습니다.

---

### 입력값 검증 수행

- User ID처럼 숫자만 필요한 값은 숫자만 입력되도록 제한합니다.
- 길이, 형식, 허용 문자 등을 기준으로 입력값을 검증합니다.

#### 핵심 효과

- 비정상적인 SQL 구문 삽입을 사전에 차단할 수 있습니다.

---

### 특수문자 및 SQL 예약어 필터링

- 작은따옴표, 주석 문자, AND, OR, SLEEP, UNION 등 공격에 사용될 수 있는 입력을 제한합니다.
- 단순 차단만으로는 우회 가능성이 있으므로 Prepared Statement와 함께 적용해야 합니다.

#### 핵심 효과

- Boolean 기반 및 Time 기반 공격 시도를 줄일 수 있습니다.

---

### 동일한 응답 처리

- 조건이 참일 때와 거짓일 때 응답 메시지가 과도하게 다르지 않도록 처리합니다.
- 존재 여부를 직접 알려주는 메시지는 최소화합니다.

#### 핵심 효과

- 공격자가 참/거짓 응답 차이를 이용해 데이터를 추론하는 것을 어렵게 만들 수 있습니다.

---

### 응답 시간 차이 최소화

- SQL 오류나 조건에 따라 응답 시간이 비정상적으로 달라지지 않도록 처리합니다.
- SLEEP 함수 등 시간 지연 공격 패턴을 탐지하고 차단합니다.

#### 핵심 효과

- Time-based Blind SQL Injection 공격을 어렵게 만들 수 있습니다.

---

### 에러 메시지 노출 차단

- SQL 오류 발생 시 DB 에러 메시지를 사용자에게 직접 출력하지 않도록 설정합니다.
- 일반적인 오류 페이지를 출력하여 내부 Query 구조가 노출되지 않도록 합니다.

#### 핵심 효과

- 공격자가 Database 구조를 파악하는 것을 어렵게 만들 수 있습니다.

---

### 최소 권한 원칙 적용

- 웹 애플리케이션에서 사용하는 DB 계정에는 필요한 최소 권한만 부여합니다.
- 조회만 필요한 기능에는 수정, 삭제, DROP 권한 등을 부여하지 않습니다.

#### 핵심 효과

- 침해가 발생하더라도 피해 범위를 최소화할 수 있습니다.

---

### WAF 적용

- Blind SQL Injection에 사용되는 반복 요청, SLEEP 함수, 비정상적인 조건 구문을 탐지하도록 설정합니다.

#### 핵심 효과

- 자동화 공격 및 반복적인 데이터 추출 시도를 탐지하고 차단할 수 있습니다.

---

## 정리

- Blind SQL Injection은 결과가 직접 출력되지 않아도 응답 차이 또는 시간 지연을 이용해 데이터를 추론하는 공격 방식입니다.

- 이번 DVWA Low 단계 실습에서는 Boolean 기반 응답 차이와 Time-based 지연을 통해 취약점 존재 여부를 확인했습니다.

- 이후 sqlmap을 이용해 Database, Table, Column, 계정 정보까지 추출할 수 있었기 때문에  
  해당 시스템은 주요정보통신기반시설 기준에서 SQL Injection 취약 상태로 판단할 수 있습니다.

- 가장 중요한 대응 방법은 Prepared Statement를 적용하여  
  SQL Query와 사용자 입력값을 분리하는 것입니다.

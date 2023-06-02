# KSPAY-PAYMENT-API
www.kspay.co.kr PHP 결제(콜백) 모듈

## 프로세스
1. 결제가 끝나면 `reCommConId`값을 CALLBACK URL로 POST 전송을합니다
2. `reCommConId` 값으로 KSPAY 서버로 소켓통신을 하여 결제 정보를 가져옵니다

## 예제코드
```php
include "KSPAY.php";

$KSPAY = new KSPAY($_POST['reCommConId']);

print_r($KSPAY->PAY_DATA());
```

## 응답값
PHP ARRAY형태로 응답하지만 보기 편하게 json_encode를 했습니다
```json
{
  "STATUS": "OK",
  "MSG": "정상적으로 결제가 완료되었습니다.",
  "PAY_DATA": {
    "RESULT": "승인 성공",
    "MSG": [
      "네이버페이 ",
      "OK: "
    ],
    "PAYMETHOD": "신용카드",
    "ORDER_NO": "KSPAY",
    "AMOUNT": "1004",
    "TRANSACTION_NO": "185560232335",
    "DATE": {
      "DATE": "20230602",
      "TIME": "144315",
      "DATE_FORMAT": "2023년 06월 02일",
      "TIME_FORMAT": "14시 43분 15초"
    },
    "AUTH_NO": " ",
    "PUCHASE_CODE": "52 ",
    "VIRTUAL_ACCOUNT": "52 ",
    "RECEIPT": "http://pgims.ksnet.co.kr/pg_infoc/src/bill/credit_view.jsp?tr_no=185560232335"
  },
  "KS_MSG": "OK: "
}
```

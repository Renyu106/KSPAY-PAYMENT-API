<?php
// Source : https://github.com/Renyu106/KSPAY-PAYMENT-API

class KSPAY
{
    private $PAYKEY;
    private $GET_PARAM;
    
    public function __construct($PAYKEY, $GET_PARAM="")
    {
        $this->PAYKEY = $PAYKEY;
        $this->GET_PARAM = empty($GET_PARAM) || false === strpos($GET_PARAM, "`") ? "authyn`trno`trddt`trdtm`amt`authno`msg1`msg2`ordno`isscd`aqucd`result`halbu`cbtrno`cbauthno" : $GET_PARAM;
    }

    public function PAY_DATA(){
        $RETURN = array();
        $KSPAY_RETURN = array();
        $rmsg = $this->SOCKET();

        if (false === strpos($rmsg, "`")) {
            $RETURN['STATUS'] = "ERR";
            $RETURN['MSG'] = "결제에 실패하였습니다. (1)";
        } else {
            $PARAM_NAME = explode("`", $this->GET_PARAM);
            $PARAM_DATA = explode("`", $rmsg);

            if (count($PARAM_NAME) < count($PARAM_DATA)) {
                for ($i = 0; $i < count($PARAM_NAME); $i++) {
                    $KSPAY_RETURN[$PARAM_NAME[$i]] = iconv("EUC-KR", "UTF-8", $PARAM_DATA[$i+1]);
                }
            }
            
            $KSPAY_DT_PAYMETHOD = $this->GET_PAYMEYHOD($KSPAY_RETURN['result'], $KSPAY_DT_PAYMETHOD_1);

            if ($KSPAY_DT_PAYMETHOD_1 == "CARD") {
                $RECEIPT = "http://pgims.ksnet.co.kr/pg_infoc/src/bill/credit_view.jsp?tr_no=" . $KSPAY_RETURN['trno'];
            } elseif ($KSPAY_DT_PAYMETHOD_1 == "VIRTUAL_ACCOUNT") {
                $RECEIPT = "http://pgims.ksnet.co.kr/pg_infoc/src/bill/ps2.jsp?s_pg_deal_numb=" . $KSPAY_RETURN['trno'];
            } else {
                $RECEIPT = "NONE";
            }

            $RETURN['STATUS'] = "OK";
            $RETURN['MSG'] = "정상적으로 결제가 완료되었습니다.";
            $RETURN['PAY_DATA'] = array(
                "RESULT" => "승인 성공",
                "MSG" => array(
                    $KSPAY_RETURN['msg1'],
                    $KSPAY_RETURN['msg2'],
                ),
                "PAYMETHOD" => $KSPAY_DT_PAYMETHOD,
                "PAYMETHOD_ID" => $KSPAY_DT_PAYMETHOD_1,
                "ORDER_NO" => $KSPAY_RETURN['ordno'],
                "AMOUNT" => $KSPAY_RETURN['amt'],
                "TRANSACTION_NO" => $KSPAY_RETURN['trno'],
                "DATE" => array(
                    "DATE" => $KSPAY_RETURN['trddt'],
                    "TIME" => $KSPAY_RETURN['trdtm'],
                    "DATE_FORMAT" => date("Y년 m월 d일", strtotime($KSPAY_RETURN['trddt'])),
                    "TIME_FORMAT" => date("H시 i분 s초", strtotime($KSPAY_RETURN['trdtm'])),
                ),
                "AUTH_NO" => (empty($KSPAY_RETURN['authno'])) ? "NONE" : $KSPAY_RETURN['authno'],
                "PUCHASE_CODE" => $KSPAY_RETURN['aqucd'],
                "VIRTUAL_ACCOUNT" => $KSPAY_RETURN['isscd'],
                "RECEIPT" => $RECEIPT,
            );
        }

        $RETURN['KS_MSG'] = $KSPAY_RETURN['msg2'];
        return $RETURN;
    }

    private $KSPAY_WEBHOST_URI = "/store/KSPayWebV1.4/web_host/recv_post.jsp";
    private $KSPAY_WEBHOST_HOST = "kspay.ksnet.to";
    private $KSPAY_WEBHOST_IP = "210.181.28.137";

    private function SOCKET()
    {
        $KSPAY_WEBHOST_HOST = "kspay.ksnet.to";
        $KSPAY_WEBHOST_IP = "210.181.28.137";
        $KSPAY_WEBHOST_URI = "/store/KSPayWebV1.4/web_host/recv_post.jsp";
        
        $PAYLOAD = "sndCommConId={$this->PAYKEY}&sndActionType={$this->MTYPE}&sndRpyParams=" . urlencode($this->GET_PARAM);
        $REQUEST = "Host: " . $KSPAY_WEBHOST_HOST . "\r\n";
        $REQUEST .= "Accept-Language: ko\r\n";
        $REQUEST .= "User-Agent: Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.0)\r\n";
        $REQUEST .= "Content-type: application/x-www-form-urlencoded\r\n";
        $REQUEST .= "Content-length: " . strlen($PAYLOAD) . "\r\n";
        $REQUEST .= "Connection: close\r\n";

		if( gethostbyname($KSPAY_WEBHOST_HOST) == $KSPAY_WEBHOST_HOST) $KSPAY_IP = $KSPAY_WEBHOST_IP;
		else $KSPAY_IP = gethostbyname($KSPAY_WEBHOST_HOST);

        $REQUEST  = "POST " . $KSPAY_WEBHOST_URI . " HTTP/1.0\r\n" . $REQUEST;
		$RPY_MSG = "";
        $REQUEST .= "\r\n";
        $REQUEST .= $PAYLOAD;
        $SOCKET = fsockopen($KSPAY_IP, 80, $errno, $errstr, 60);
        if ($SOCKET) {
            fwrite($SOCKET, $REQUEST, strlen($REQUEST));
            fflush($SOCKET);
            while (!feof($SOCKET)) {
                $RPY_MSG .= fread($SOCKET, 8192);
            }
            fclose($SOCKET);
            $rpos = strpos($RPY_MSG, "\r\n\r\n");
            if ($rpos !== false) {
                return substr($RPY_MSG, $rpos + 4);
            }
        }
        return "";
    }

    private function GET_PAYMEYHOD($RESULT, &$PAYMETHOD)
    {
        $PAYMETHOD = "";
        switch (substr($RESULT, 0, 1)) {
            case '1':
            case 'I':
                $PAYMETHOD = "CARD";
                return "신용카드";
            case '2':
                $PAYMETHOD = "TRANSFER";
                return "실시간 계좌 이체";
            case '6':
                $PAYMETHOD = "VIRTUAL_ACCOUNT";
                return "가상 계좌";
            case 'M':
                return "휴대폰 결제";
            case 'G':
                return "상품권";
            default:
                return "알 수 없음";
        }
    }
}

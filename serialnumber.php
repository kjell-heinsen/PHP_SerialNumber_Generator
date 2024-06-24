<?php

class serialnumber {



    private ?string $_suffix = NULL;
    private ?int $_numsegments = NULL;
    private ?int $_numsegchars = NULL;
    private ?string $_tokens = NULL;
    private ?string $_serialnumber = NULL;

    public static function instance() {
        return new serialnumber();
    }
    public function __call($name, $arguments) {
        if ($name === 'generate') {
            call_user_func(array($this, 'sendGenerate'));
        }
        if ($name === 'verify') {
            call_user_func(array($this, 'sendVerify'));
        }
    }

    public static function __callStatic($name, $arguments) {
        if ($name === 'generate') {
            call_user_func_array(array('serialnumber', 'static_generate'), $arguments);
        }
        if ($name === 'verify') {
            call_user_func_array(array('serialnumber', 'static_verify'), $arguments);
        }
    }

    public function setSegmentNr(int $number):serialnumber{
        $this->_numsegments = $number;
        return $this;
    }

    public function setCharNr(int $number):serialnumber{
        $this->_numsegchars = $number;
        return $this;
    }

    public function setSuffix(string $suffix):serialnumber{
        $this->_suffix = $suffix;
        return $this;
    }
    public function setTokens(string $tokens):serialnumber{
        $this->_tokens = $tokens;
        return $this;
    }
    public function setSerial(string $serial):serialnumber {
        $this->_serialnumber = $serial;
        return $this;
    }

    public function sendVerify():bool {
       return self::static_verify($this->_serialnumber);
    }

    public function sendGenerate():string{
        if ($this->_numsegments === NULL || $this->_numsegchars === NULL) {
            throw new Exception("Segments and characters must be set");
        }
     return self::static_generate($this->_numsegments,$this->_numsegchars,$this->_suffix,$this->_tokens);

    }




    public static function static_generate(int $segments,int $segment_chars,string $suffix = NULL,string $token = NULL):string {

        $tokens = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

        if(!is_null($token)){
            $tokens = $token;
        }

        $serial = '';

        for ($i = 0; $i < $segments; $i++) {
            $segment = '';
            for ($j = 0; $j < $segment_chars; $j++) {
                $segment .= $tokens[rand(0, strlen($tokens) - 1)];
            }
            $serial .= $segment;


            if ($i < ($segments - 1)) {
                $serial .= '-';
            }
        }


        if (isset($suffix)) {
            if (is_numeric($suffix)) {
                $serial .= '-' . strtoupper(base_convert($suffix, 10, 36));
            } else {
                $long = sprintf("%u", ip2long($suffix), true);
                if ($suffix === long2ip($long)) {
                    $serial  .= '-' . strtoupper(base_convert($long, 10, 36));
                } else {
                    $serial  .= '-' . strtoupper(str_ireplace(' ', '-', $suffix));
                }
            }
        }

        $checksum = strtoupper(base_convert(md5($serial), 16, 36));
        $checksum = substr($checksum, 0, $segment_chars);
        $serial  .= '-' . $checksum;


       return $serial;
    }


    public static function static_verify(string $license):bool {

        $segments = explode('-', $license);
        $checksum = end($segments);
        array_pop($segments);
        $license_base = implode('-', $segments);
        $computed_checksum = strtoupper(base_convert(md5($license_base), 16, 36));
        $computed_checksum = substr($computed_checksum, 0, strlen($checksum));
        return $checksum === $computed_checksum;
    }



}
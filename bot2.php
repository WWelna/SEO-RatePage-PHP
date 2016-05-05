<?php

/* @Sanguinarious <Sanguinarious@OccultusTerra.com> */

class gen_bot {

	public $proxy_port = 0;
	public $proxy_host = "";
	public $proxy_type = "";

	public $user_agent = "Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 6.0)";

	private $offset = 0;
	private $cookies = "";

	private $filename = "";
	private $handle = null;

	public function set_cookies($filename) {
		$this->cookies = $filename;
		return $this->cookies;
	}

	public function reset_offset() {
		$this->offset = 0;
		return $this->offset;
	}

	public function get_cookies() {
		return $this->cookies;
	}

	public function set_shit($filename) {
		$this->handle = fopen($filename, 'a+');
		if($this->handle == false) {
			return false;
		}
		return true;
	}

	public function write_shit($shit) {
		return fwrite($this->handle, $shit);
	}

        public function encode($text) {
                $return = '';
                for($i = 0; $i < strlen($text); $i++) {
                        $return .= '%'.bin2hex(substr($text, $i, 1));
                }
                return $return;
        }

	public function curl_get($url, $ref="", $post="") {
		$ch = curl_init();
		curl_setopt ($ch, CURLOPT_URL, $url);
		curl_setopt ($ch, CURLOPT_CONNECTTIMEOUT, 30);
		curl_setopt ($ch, CURLOPT_RETURNTRANSFER, true);
		//curl_setopt ($ch, CURLOPT_HEADER, true);
		//curl_setopt ($ch, CURLOPT_FOLLOWLOCATION, true);
		if($ref != "") {
			curl_setopt ($ch, CURLOPT_REFERER, $ref);
		}
		if($this->cookies != "") {
			curl_setopt ($ch, CURLOPT_COOKIEJAR, $this->cookies);
			curl_setopt ($ch, CURLOPT_COOKIEFILE, $this->cookies);
		}
		curl_setopt ($ch, CURLOPT_USERAGENT, $this->user_agent);
		if($post != "") {
			curl_setopt ($ch, CURLOPT_POST, true);
			curl_setopt ($ch, CURLOPT_POSTFIELDS, $post);
		}
		if($this->proxy_host != "") {
			//curl_setopt ($ch, CURLOPT_HTTPPROXYTUNNEL, true);
			//curl_setopt ($ch, CURLOPT_PROXYPORT, $this->proxy_port);
			curl_setopt ($ch, CURLOPT_PROXYTYPE, CURLPROXY_SOCKS5);
			//curl_setopt ($ch, CURLOPT_PROXYTYPE, $this->proxy_type);
			//curl_setopt ($ch, CURLOPT_PROXY, $this->proxy_host);
			curl_setopt ($ch, CURLOPT_PROXY, $this->proxy_host.":"."$this->proxy_port");
		}
		$page = curl_exec($ch);
		curl_close($ch);
		return $page;
	}

	public function curl_down($url, $file_path="", $ref="", $post="") {
		if(file_exists(dirname(__FILE__).'/'.$file_path.basename($url))) {
			return 0;
		}
		$fp = fopen(dirname(__FILE__).'/'.$file_path.basename($url), 'w+');
		if($fp == FALSE) {
			return FALSE;
		}
		$ch = curl_init();
		curl_setopt ($ch, CURLOPT_URL, $url);
		curl_setopt ($ch, CURLOPT_CONNECTTIMEOUT, 30);
		curl_setopt ($ch, CURLOPT_FILE, $fp);
		//curl_setopt ($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt ($ch, CURLOPT_CONNECTTIMEOUT, 0);
		if($ref != "") {
			curl_setopt ($ch, CURLOPT_REFERER, $ref);
		}
		if($this->cookies != "") {
			curl_setopt ($ch, CURLOPT_COOKIEJAR, $this->cookies);
			curl_setopt ($ch, CURLOPT_COOKIEFILE, $this->cookies);
		}
		curl_setopt ($ch, CURLOPT_USERAGENT, $this->user_agent);
		if($post != "") {
			curl_setopt ($ch, CURLOPT_POST, true);
			curl_setopt ($ch, CURLOPT_POSTFIELDS, $post);
		}
		if($this->proxy_host != "") {
			curl_setopt ($ch, CURLOPT_HTTPPROXYTUNNEL, true);
			curl_setopt ($ch, CURLOPT_PROXYPORT, $this->proxy_port);
			curl_setopt ($ch, CURLOPT_PROXYTYPE, $this->proxy_type);
			curl_setopt ($ch, CURLOPT_PROXY, $this->proxy_host);
		}
		curl_exec($ch);
		curl_close($ch);
		fclose($fp);
		return 0;
	}

	public function curl_multi_down($list, $base_dir="", $ref, $post="") {
		$active = null;
		$mh = curl_multi_init();
		foreach($list as $i => $url) {
			if(file_exists(dirname(__FILE__).'/'.$base_dir.'/'.basename($url)) == true) {
				$fp[$i] = false;
			}
			if($url != "" AND !isset($fp[$i])) {
				$fp[$i] = fopen(dirname(__FILE__).'/'.$base_dir.'/'.basename($url), 'w+');
			}
			else {
				$fp[$i] = false;
			}
			if($fp[$i] != false) { // File can be written so fetch shit	
				$c[$i] = curl_init();	
				curl_setopt ($c[$i], CURLOPT_URL, $url);
				curl_setopt ($c[$i], CURLOPT_CONNECTTIMEOUT, 30);
				curl_setopt ($c[$i], CURLOPT_FILE, $fp[$i]);
				curl_setopt ($c[$i], CURLOPT_FOLLOWLOCATION, true);
				if($ref != "") {
					curl_setopt ($c[$i], CURLOPT_REFERER, $ref);
				}
				if($this->cookies != "") {
					curl_setopt ($c[$i], CURLOPT_COOKIEJAR, $this->cookies);
					curl_setopt ($c[$i], CURLOPT_COOKIEFILE, $this->cookies);
				}
				curl_setopt ($c[$i], CURLOPT_USERAGENT, $this->user_agent);
				if($post != "") {
					curl_setopt ($c[$i], CURLOPT_POST, true);
					curl_setopt ($c[$i], CURLOPT_POSTFIELDS, $post);
				}
				if($this->proxy_host != "") {
					curl_setopt ($c[$i], CURLOPT_HTTPPROXYTUNNEL, true);
					curl_setopt ($c[$i], CURLOPT_PROXYPORT, $this->proxy_port);
					curl_setopt ($c[$i], CURLOPT_PROXYTYPE, $this->proxy_type);
					curl_setopt ($c[$i], CURLOPT_PROXY, $this->proxy_host);
				}
				curl_multi_add_handle($mh, $c[$i]);
			}
		}
		do {
			usleep(10000);
			$n=curl_multi_exec($mh, $active);
		} while($active);
		if(isset($c)) {
			foreach($c as $i2 => $ch2) {
				curl_multi_remove_handle($mh, $ch2);
				curl_close($ch2);
				fclose($fp[$i2]);
				unset($fp[$i2]);
				unset($c[$i2]);
				unset($list[$i2]);
			}
		}
		curl_multi_close($mh);
	}

	public function get_between($string, $start, $end) {
                $string = " ".$string;
                $ini = strpos($string,$start);
                if ($ini == 0) return "";
                $ini += strlen($start);
                $len = strpos($string,$end,$ini) - $ini;
                return substr($string,$ini,$len);
        }

	public function get_between_o($string, $start, $end) {
		$string = " ".$string;
		$ini = strpos($string, $start, $this->offset);
		if($ini == 0) return "";
		$ini += strlen($start);
		$len = strpos($string, $end, $ini) - $ini;
		$this->offset = $ini + $len;
		return substr($string, $ini, $len);
	}

	public function recaptcha_fetch_image($key) {
		$p = $this->curl_get("http://www.google.com/recaptcha/api/noscript?k=".$key);
		$image = $this->get_between($p, '<img width="300" height="57" alt="" src="', '"');
		$image = "http://www.google.com/recaptcha/api/".$image;
		$challenge = $this->get_between($p, 'id="recaptcha_challenge_field" value="', '"');
		$ret[] = array('image' => $image,
			       'challenge' => $challenge);
		return $ret;
	}

	public function __destruct() {
		if($this->handle != false OR $this->handle != null) {
			fclose($this->handle);
		}
	}

}


?>

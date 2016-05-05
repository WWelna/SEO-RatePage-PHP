<?php

/*
* Copyright (C) 2011, William H. Welna All rights reserved.
*
* Redistribution and use in source and binary forms, with or without
* modification, are permitted provided that the following conditions are met:
*     * Redistributions of source code must retain the above copyright
*       notice, this list of conditions and the following disclaimer.
*     * Redistributions in binary form must reproduce the above copyright
*       notice, this list of conditions and the following disclaimer in the
*       documentation and/or other materials provided with the distribution.
*
* THIS SOFTWARE IS PROVIDED BY William H. Welna ''AS IS'' AND ANY
* EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
* WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
* DISCLAIMED. IN NO EVENT SHALL William H. Welna BE LIABLE FOR ANY
* DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
* (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
* LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
* ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
* (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
* SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
*/

/* Simple page cache for crawling a website / only fetch a link once instead of multiple times */

/* Example Usage:
 *
 * function getUrls($BaseUrl) {
 *	global $thecache;
 *	$sitemap = new sitemap();
 *	$sitemap->set_ignore(array("javascript:", ".css", ".js", ".pdf",
 *	 						   ".ico", ".jpg", ".png", ".jpeg",
 *							   ".swf", ".gif", ".zip", ".rar", 
 *							   ".7z", ".flv", "#", "/feed/", 
 *							   "/trackback/", "tel://",
 *							   "/tag/", "mailto:", " "));
 *
 *	//parsing one page and gathering links
 *	$sitemap->get_links($BaseUrl);
 *	$map = $sitemap->generate_sitemap();
 *	$thecache = new PageCache($sitemap->get_cache()); // Load the cache copies of all the pages;
 *	return $sitemap->get_array();
 * }
 */

include_once('bot2.php');

class PageCache {
	private $page_cache = array();
	private $cache_stats = array();
	private $bot;

	public function __construct($cache=array()) {
		foreach($cache as $key => $value) {
			$this->cache_stats[$this->url_parse($key)] = 1;
			$this->page_cache[$this->url_parse($key)] = $value;
			$this->bot = new gen_bot();
		}
	}
	
	public function cache_url($url) { // just downloads and stores the page in the cache
		//echo "<p>Caching $url</p>";
		$cache_url = $this->url_parse($url);
		if(!isset($this->page_cache[$cache_url])) {
			if(!isset($this->cache_stats[$this->url_parse($url)]))
				$this->cache_stats[$this->url_parse($url)] = 0;
			$this->cache_stats[$this->url_parse($url)] += 1;
			$this->page_cache[$cache_url] = $this->bot->curl_get($url);
			return $this->page_cache[$cache_url];
		}
		return "";
	}

	public function get_page($url) { // If page doesn't exist in cache get it and cache it
		if(!isset($this->page_cache[$this->url_parse($url)])) {
			return $this->cache_url($url);
		} else {
			//echo "<p>Returning Cached Page $url</p>";
			$this->cache_stats[$this->url_parse($url)] += 1;
			return $this->page_cache[$this->url_parse($url)];
		}
	}

	private function url_parse($url) { // removes HTTP:// and trailing / 
		$original = $url;
		if(strlen($url) < 3) return "NULL";
		if(!($url=str_replace("http://", "", strtolower($url)))) {
			$url = $original;
		}
		if($url[strlen($url)-1] == '/')
			$url[strlen($url)-1] = '';
		//echo "<p>".$original." -> ".$url." -> ".hash('md5', $url)."</p>";
		return hash('md5', $url);
	}

	public function dump_stats() {
		$saved_requests = 0;
		//echo "<p><b>PageCache Stats</b></p>";
		foreach($this->cache_stats as $key => $value) {
			//echo "<p>$key accessed $value times</p>";
			if($value > 1)
				$saved_requests += ($value-1);
		}
		//echo "<p>Saved $saved_requests number of page requests</p>";
	}

	public function __destruct() {
	
	}
}

?>

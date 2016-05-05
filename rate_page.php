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

/* Basic Usage:
 *
 * $SEO = new RatePage($content_of_page, $keyword_to_search);
 * $keyword_score = $SEO->calculate_score();
 * $array_of_suggestions_to_improve_SEO_Rating = $SEO->suggestions();
 *
 * Need to calculate score before getting suggestions obviously, etc.
 *
 */

class RatePage {
	// Private Variables
	private $P_score=0;
	private $P_suggestions = array();
	private $P_page="", $P_page_txt="", $P_page_lower=""; // page information
	private $P_words=0; // Word count of article
	private $P_keyword="", $P_keyword_count=0; // Keyword to check
	private $P_h_count=0;
	// Cached Checks
	private $P_keyword_title_check=false, $P_keyword_h1_check=false, $P_keyword_h2_check=false, $P_keyword_h3_check=false, $P_keyword_bold_check=false, $P_keyword_italics_check=false, $P_keyword_underline_check=false, $P_keyword_altimg_check=false, $P_keyword_first_check=false, $P_keyword_last_check=false, $P_keyword_metatags_check=false, $P_keyword_incontent_check=false, $P_keyword_nofollow_check=false;
	private $P_keyword_title_result=false, $P_keyword_h1_result=false, $P_keyword_h2_result=false, $P_keyword_h3_result=false, $P_keyword_bold_result=false, $P_keyword_italics_result=false, $P_keyword_underline_result=false, $P_keyword_altimg_result=false, $P_keyword_first_result=false, $P_keyword_last_result=false, $P_keyword_metatags_result=false, $P_keyword_incontent_result=false, $P_keyword_nofollow_result=false;
	// Public Variables
	// Construct
	public function __construct($page_content, $keyword) {
		$this->P_page = $page_content;
		$this->P_keyword = strtolower($keyword);
		$this->strip_html(); // Create text only version of page
		$this->keyword_density(); // Calculate keyword density
		$this->content_words(); // Calculate page's word count
	}
	// Calculate Score
	public function calculate_score() {
		// Keyword is in bold
		if($this->keyword_bold())
			$this->P_score += 6;
		else
			$this->P_score -= 1;
		// Keyword in italics
		if($this->keyword_italics())
			$this->P_score += 4;
		// Keyword is underlined
		if($this->keyword_underline())
			$this->P_score += 3;
		// Keyword Density Score
		if($this->P_keyword_count < 1)
			$this->P_score -= 2;
		elseif($this->P_keyword_count >= 1 && $this->P_keyword_count < 2)
			$this->P_score += 4;
		elseif($this->P_keyword_count >= 2 && $this->P_keyword_count < 5)
			$this->P_score += 6;
		elseif($this->P_keyword_count >= 5 && $this->P_keyword_count <= 6)
			$this->P_score += 2;
		elseif($this->P_keyword_count > 6)
			$this->P_score -= 2;
		// Content Score
		if($this->P_words <= 200)
			$this->P_score -= 2;
		if($this->P_words >= 350 && $this->P_words < 500)
			$this->P_score += 2;
		if($this->P_words >= 500 && $this->P_words <= 700)
			$this->P_score += 4;
		elseif($this->P_words > 700)
			$this->P_score += 6;
		// Keyword in content?
		if($this->keyword_incontent())
			$this->P_score += 10;
		else
			$this->P_score  -= 2;
		// <h1> <h2> <h3> Score
		if($this->keyword_h1())
			$this->P_score += 9;
		else
			$this->P_score -= 1;
		if($this->keyword_h2())
			$this->P_score += 8;
		else
			$this->P_score -= 1;
		if($this->keyword_h3())
			$this->P_score += 5;
		// Location Score
		/* if($this->keyword_first())
			$this->P_score += 7;
		else
			$this->P_score -= 1; 
 		if($this->keyword_last())
			$this->P_score += 4; */
		// Keyword in Alternate Text
		if($this->keyword_altimg())
			$this->P_score += 6;
		else
			$this->P_score -= 1;
		// Keyword in metatag
		if($this->keyword_metatags())
			$this->P_score += 15;
		else
			$this->P_score -= 2;
		// Links have nofollow?
		if($this->keyword_nofollow())
			$this->P_score += 5;
		else
			$this->P_score -=2;
		return number_format(($this->P_score + 15) / 90 * 100, 2);
	}
	// Checks
	public function keyword_title() {
		if(!$this->P_keyword_title_check) {
			$this->P_keyword_title_check = true;
			if(preg_match("/<title>(.+)<\\/title>/i", $this->tolower(), $match)) {
				if(substr_count($match[0], $this->P_keyword)) {
					$this->P_keyword_title_result=true;
				}
			} else { // There isn't any title?
				$this->P_suggestions[] = "<li class=\"error\">You currently have no &lt;title&gt; Tag</li>";
			}
			if($this->P_keyword_title_result=true) {
				$this->P_suggestions[] = "<li class=\"success\">Your title contains your keyword</li>";
			}
		}
		return $this->P_keyword_title_result;
	}
	public function keyword_h1() {
		if(!$this->P_keyword_h1_check) {
			$this->P_keyword_h1_check = true;
			if(preg_match_all("/<h1>(.+)<\\/h1>/", $this->tolower(), $matches)) {
				foreach($matches as $match1) {
					foreach($match1 as $match) {
						if(substr_count($match, $this->P_keyword)) {
							$this->P_h_count += 1;
							$this->P_keyword_h1_result=true;
						}
					}
				}
				if(!$this->P_keyword_h1_check) {
					$this->P_suggestions[] = "<li class=\"error\">Didn't have '".$this->P_keyword."' in any &lt;h1&gt;</li>";
				}
			} else { // There isn't any title?
				$this->P_suggestions[] = "<li class=\"error\">You have no &lt;h1&gt; tags</li>";
			}
		}
		return $this->P_keyword_h1_result;
	}
	public function keyword_h2() {
		if(!$this->P_keyword_h2_check) {
			$this->P_keyword_h2_check = true;
			if(preg_match_all("/<h2>(.+)<\\/h2>/", $this->tolower(), $matches)) {
				foreach($matches as $match1) {
					foreach($match1 as $match) {
						if(substr_count($match, $this->P_keyword)) {
							$this->P_h_count += 1;
							$this->P_keyword_h2_result=true;
						}
					}
				}
				if(!$this->P_keyword_h2_result) {
					$this->P_suggestions[] = "<li class=\"error\">Didn't have '".$this->P_keyword."' in any &lt;h2&gt;</li>";
				} else {
					$this->P_suggestions[] = "<li class=\"success\">You have '".$this->P_keyword."' in at least one &lt;h2&gt;</li>";
				}
			} else { // There isn't any title?
				$this->P_suggestions[] = "<li class=\"error\">You have no &lt;h2&gt; tags</li>";
			}
		}
		return $this->P_keyword_h2_result;
	}	
	public function keyword_h3() {
		if(!$this->P_keyword_h3_check) {
			$this->P_keyword_h3_check = true;
			if(preg_match_all("/<h3>(.+)<\\/h3>/", $this->tolower(), $matches)) {
				foreach($matches as $match1) {
					foreach($match1 as $match) {
						if(substr_count($match, $this->P_keyword)) {
							$this->P_h_count += 1;
							$this->P_keyword_h3_result=true;
						}
					}
				}
				if(!$this->P_keyword_h3_check) {
					$this->P_suggestions[] = "<li class=\"error\">Didn't have '".$this->P_keyword."' in any &lt;h3&gt;</li>";
				} else {
					$this->P_suggestions[] = "<li class=\"success\">You have '".$this->P_keyword."' in at least one &lt;h3&gt;</li>";
				}
			} else { // There isn't any title?
				$this->P_suggestions[] = "<li class=\"error\">You have no &lt;h3&gt; tags</li>";
			}
		}
		return $this->P_keyword_h3_result;
	}
	public function keyword_bold() {
		if(!$this->P_keyword_bold_check) {
			$this->P_keyword_bold_check = true;
			if(preg_match_all("/<b>(.+)<\\/b>/", $this->tolower(), $matches)) {
				foreach($matches as $match1) {
					foreach($match1 as $match) {
						if(substr_count($match, $this->P_keyword)) {
							$this->P_keyword_bold_result=true;
						}
					}
				}
				if(!$this->P_keyword_bold_result) {
					$this->P_suggestions[] = "<li class=\"error\">Didn't have '".$this->P_keyword."' in any &lt;b&gt;;</li>";
				} else {
					$this->P_suggestions[] = "<li class=\"success\">You have '".$this->P_keyword."' in at least one &lt;b&gt;";
				}
			} else { // There isn't any title?
				$this->P_suggestions[] = "<li class=\"error\">You have no &lt;b&gt; tags</li>";
			}
		}
		return $this->P_keyword_bold_result;
	}
	public function keyword_italics() {
		if(!$this->P_keyword_italics_check) {
			$this->P_keyword_italics_check = true;
			if(preg_match_all("/<i>(.+)<\\/i>/", $this->tolower(), $matches)) {
				foreach($matches as $match1) {
					foreach($match1 as $match) {
						if(substr_count($match, $this->P_keyword)) {
							$this->P_keyword_italics_result=true;
						}
					}
				}
				if(!$this->P_keyword_italics_result) {
					$this->P_suggestions[] = "<li class=\"error\">Didn't have '".$this->P_keyword."' in any &lt;i&gt;</li>";
				} else {
					$this->P_suggestions[] = "<li class=\"success\">You have '".$this->P_keyword."' in &lt;i&gt;</li>";
				}
			} else { // There isn't any title?
				$this->P_suggestions[] = "<li class=\"error\">You have no &lt;i&gt; tags</li>";
			}
		}
		return $this->P_keyword_italics_result;
	}
	public function keyword_underline() {
		if(!$this->P_keyword_underline_check) {
			$this->P_keyword_underline_check = true;
			if(preg_match_all("/<u>(.+)<\\/u>/", $this->tolower(), $matches)) {
				foreach($matches as $match1) {
					foreach($match1 as $match) {
						if(substr_count($match, $this->P_keyword)) {
							$this->P_keyword_underline_result=true;
						}
					}
				}
				if(!$this->P_keyword_underline_result) {
					$this->P_suggestions[] = "<li class=\"error\">Didn't have '".$this->P_keyword."' in any &lt;u&gt;";
				} else {
					$this->P_suggestions[] = "<li class=\"success\">You have '".$this->P_keyword."' in at least one &lt;u&gt;";
				}
			} else { // There isn't any title?
				$this->P_suggestions[] = "<li class=\"error\">You have no &lt;u&gt; tags</li>";
			}
		}
		return $this->P_keyword_underline_result;
	}
	public function keyword_altimg() {
		if(!$this->P_keyword_altimg_check) {
			$this->P_keyword_altimg_check = true;
			if(preg_match_all("/<img(.+)>/", $this->tolower(), $matches)) {
				foreach($matches as $match1) {
					foreach($match1 as $match) {
						if(preg_match("/alt='(.+)'/", $match, $alttxt) OR preg_match("/alt=\"(.+)\"/", $match, $alttxt)) { 
							if(substr_count($alttxt[0], $this->P_keyword)) {
								$this->P_keyword_altimg_result=true;
							}
						}
					}
				}
				if(!$this->P_keyword_altimg_result) {
					$this->P_suggestions[] = "<li class=\"error\">Didn't have '".$this->P_keyword."' in any images' alt text</li>";
				} else {
					$this->P_suggestions[] = "<li class=\"success\">You have '".$this->P_keyword."' in at least one image alt text</li>";
				}
			} else { // There isn't any title?
				$this->P_suggestions[] = "<li class=\"error\">You have no images</li>";
			}
		}
		return $this->P_keyword_altimg_result;
	}
	public function keyword_nofollow() {
		$done_suggestion = false;
		if(!$this->P_keyword_nofollow_check) {
			$this->P_keyword_nofollow_check = true;
			if(preg_match_all("/<a(.+)>/", $this->tolower(), $matches)) {
				foreach($matches as $match1) {
					foreach($match1 as $match) {
						if(preg_match("/rel='(.+)'/", $match, $alttxt) OR preg_match("/rel=\"(.+)\"/", $match, $alttxt)) { 
							if(substr_count($alttxt[1], "nofollow")) {
								$this->P_keyword_nofollow_result=true;
							}
						} 
					}
				}
				if(!$this->P_keyword_nofollow_result) {
					$this->P_suggestions[] = "<li class=\"error\">Didn't use any nofollow directives in your links</li>";
				} else {
					$this->P_suggestions[] = "<li class=\"success\">Your links use the nofollow directive!</li>";
				}
			} 
		} else { // There isn't any title?
			$this->P_suggestions[] = "<li class=\"error\">You have no links</li>"; // ? lol
		} 
		return $this->P_keyword_nofollow_result;
	}

	public function keyword_incontent() {
		if(!$this->P_keyword_incontent_check) {
			$this->P_keyword_incontent_check = true;
			if(substr_count($this->strip_html(), $this->P_keyword))
				$this->P_keyword_incontent_result = true;
		}
		return $this->P_keyword_incontent_result;
	}
	public function keyword_metatags() {
		if(!$this->P_keyword_metatags_check) {
			$this->P_keyword_metatags_check = true;
			if(preg_match_all("/<meta name=\"(.+)\" content=\"(.+)\"/", $this->tolower(), $matches) OR preg_match_all("/<meta name='(.+)' content='(.+)'/", $this->tolower(), $matches)) {
				foreach($matches as $match1) {
					foreach($match1 as $match) {
						if(substr_count($match, $this->P_keyword)) {
							$this->P_keyword_metatags_result=true;
						}
					}
				}
				if(!$this->P_keyword_metatags_result) {
					$this->P_suggestions[] = "<li class=\"error\">Didn't have '".$this->P_keyword."' in any Meta Tags</li>";
				}
				if($this->P_keyword_metatags_result) {
					$this->P_suggestions[] = "<li class=\"success\">You have the keyword '".$this->P_keyword."' in your Meta Tags</li>";
				}
			} else { // There isn't any title?
				$this->P_suggestions[] = "You have no Meta Tags tags";
			}
		}
		return $this->P_keyword_metatags_result;
	}
	// Convert everything to lower case to make things easier
	private function tolower() {
		if($this->P_page_lower=="") {
			$this->P_page_lower = strtolower($this->P_page);
		}
		return $this->P_page_lower;
	}
	// Calculate content size
	private function content_words() {
		if($this->P_words == 0) {
			$this->P_words = str_word_count($this->strip_html());
			if($this->P_words < 300) {
				$this->P_suggestions[] = "<li class=\"error\">Content word count less than 300</li>";
			} else {
				$this->P_suggestions[] = "<li class=\"success\">Content word count more than 300!</li>";
			}
		}
		return $this->P_words;
	}
	// Strip HTML tags to text/content only
	private function strip_html() {
		if($this->P_page_txt == "") { // Have not cached a striped copy yet
			$Rules = array ('@<script[^>]*?>.*?</script>@si', '@<[\/\!]*?[^<>]*?>@si', '@([\r\n])[\s]+@', '@&(quot|#34);@i', '@&(amp|#38);@i', '@&(lt|#60);@i', '@&(gt|#62);@i', '@&(nbsp|#160);@i', '@&(iexcl|#161);@i', '@&(cent|#162);@i', '@&(pound|#163);@i', '@&(copy|#169);@i', '@&(reg|#174);@i','@&#(d+);@e');    
			$Replace = array ('', ' ', '1', '"', '&', '<', '>', ' ', chr(161), chr(162), chr(163), chr(169), chr(174), 'chr()');
			$this->P_page_txt = strtolower(preg_replace($Rules, $Replace, $this->P_page));
		}
		return $this->P_page_txt;
	}
	// How many times a keyword shows up
	private function keyword_density() {
		if(!$this->P_keyword_count)
			$this->P_keyword_count = substr_count($this->strip_html(), $this->P_keyword);
		return $this->P_keyword_count;
	}
	// Offer suggestions/improvements
	public function suggestions() {
		return $this->P_suggestions;
	}
	// Destruct
	public function __destruct() {
	}
} 
?>

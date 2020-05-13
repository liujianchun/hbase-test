<?php
/**
 * @name TCApiControllerBase
 * @author liujianchun
 */
class TCHtml{
	public static function element($tag, $value, $options, $to_encode_html = true){
		$options_str = "";
		foreach($options as $k=>$v){
			if($v===null) continue;
			$v = htmlspecialchars($v);
			$options_str .= " {$k}=\"{$v}\"";
		}
		if($tag != 'textarea' && empty($value)){
			return "<{$tag}{$options_str}/>";
		}elseif($to_encode_html)
			return "<{$tag}{$options_str}>" . htmlspecialchars($value) . "</{$tag}>";
		else
			return "<{$tag}{$options_str}>" . $value . "</{$tag}>";
	}
	
	
	public static function link($text, $href, $options=array()){
		$options['href'] = $href;
		return self::element('a', $text, $options);
	}
	
	
	public static function linkButton($text, $submit, $data=array(), $options=array()){
	  if(!empty($options['confirm'])){
	    $confirm = str_replace('"', '\\"', $options['confirm']);
	    $confirm = str_replace("'", "\\'", $confirm);
	    $confirm = htmlspecialchars($confirm);
	    $options['href'] = 'javascript:if(confirm("'.$confirm.'")) buildPostFormAndSubmit.apply(this, ["'.$submit.'", ' . json_encode($data) . '])';
	    unset($options['confirm']);
	  }else{
		  $options['href'] = 'javascript:buildPostFormAndSubmit.apply(this, ["'.$submit.'", ' . json_encode($data) . '])';
	  }
		return self::element('a', $text, $options);
	}
	
	
	
	public static function pagination($page_count, $current_page=null, $base_uri=null){
		if($current_page==null) $current_page = isset($_GET['page'])?intval($_GET['page']):0;
		if(empty($base_uri)) $base_uri = $_SERVER['REQUEST_URI'];
		$base_uri = preg_replace('/([\\?&])page=[^&]+/', '$1', $base_uri);
		$base_uri = preg_replace('/&+/', '&', $base_uri);
		$base_uri = str_replace('?&', '?', $base_uri);
		$base_uri = trim($base_uri, '?&');
		
		$shown_pages = array();
		if($page_count<=10){
			$shown_pages[] = array('from'=>0, 'to'=>10);
		}else{
			// build the first segment
			if($current_page <= 7){
				if($page_count<15){
					$shown_pages[] = array('from'=>0, 'to'=>$page_count-1);
				}else{
					$shown_pages[] = array('from'=>0, 'to'=>7);
					$shown_pages[] = array('from'=>$page_count-3, 'to'=>$page_count-1);
				}
			}else{
				$shown_pages[] = array('from'=>0, 'to'=>4);
				if($page_count < $current_page+8){
					$shown_pages[] = array('from'=>$current_page-2, 'to'=>$page_count-1);
				}else{
					$shown_pages[] = array('from'=>$current_page-2, 'to'=>$current_page+2);
					$shown_pages[] = array('from'=>$page_count-3, 'to'=>$page_count-1);
				}
			}
		}
		
		$html = '<nav><ul class="pagination">';
		if($current_page<=0){
			$html .= '<li class="disabled">';
			$html .= '<a href="javascript:void(0)"><span aria-hidden="true">&laquo;</span><span class="sr-only">Previous</span></a>';
			$html .= '</a>';
		}else{
			$html .= '<li>';
			$html .= '<a href="';
			$html .= self::buildHrefOfPage($base_uri, $current_page-1);
			$html .= '"><span aria-hidden="true">&laquo;</span><span class="sr-only">Previous</span></a>';
			$html .= '</a>';
		}
		foreach($shown_pages as $i=>$segment){
			if($i>0) $html .= '<li class="disabled"><a href="javascript:void(0)">...</a></li>';
			for($p=$segment['from']; $p<=$segment['to']; $p++){
				if($p>=$page_count) break;
				$html .= '<li'; 
				if($p==$current_page) $html .= ' class="active"';
				$html .= '>';
				$html .= '<a href="' . self::buildHrefOfPage($base_uri, $p) . '">' . ($p+1) . '</a>';
				$html .= '</li>';
			}
		}
		if($current_page>=$page_count-1){
			$html .= '<li class="disabled">';
			$html .= '<a href="javascript:void(0)"><span aria-hidden="true">&raquo;</span><span class="sr-only">Next</span></a>';
			$html .= '</a>';
		}else{
			$html .= '<li>';
			$html .= '<a href="';
			$html .= self::buildHrefOfPage($base_uri, $current_page+1);
			$html .= '"><span aria-hidden="true">&raquo;</span><span class="sr-only">Next</span></a>';
			$html .= '</a>';
		}
		$html .= '</ul></nav>';
		return $html;
	}
	
	private static function buildHrefOfPage($base_uri, $page){
		if(strpos($base_uri, '?')===false) return $base_uri . '?page=' . $page;
		else return $base_uri . '&page=' . $page;
	}
}


<?php
$nm_bulan=array('01'=>'Januari','02'=>'Pebruari','03'=>'Maret','04'=>'April','05'=>'Mei','06'=>'Juni','07'=>'Juli','08'=>'Agustus','09'=>'September',
                    '10'=>'Oktober','11'=>'Nopember','12'=>'Desember','1'=>'Januari','2'=>'Pebruari','3'=>'Maret','4'=>'April','5'=>'Mei','6'=>'Juni',
                    '7'=>'Juli','8'=>'Agustus','9'=>'September',);
$hari = array ( 1 =>    'Senin',
            'Selasa',
            'Rabu',
            'Kamis',
            'Jumat',
            'Sabtu',
            'Minggu'
        );
if(!function_exists('pilihbulan')){ 
    function pilihbulan($bln){
      switch ($bln){case "01": return "Januari"; break; case "02": return "Pebruari"; break; case "03": return "Maret"; break; case "04": return "April"; break; case "05": return "Mei"; break; case "06": return "Juni"; break; case "07": return "Juli"; break; case "08": return "Agustus"; break; case "09": return "September"; break; case "10": return "Oktober"; break; case "11": return "Nopember"; break; case "12": return "Desember"; break; } }
} 
if(!function_exists('format_uang')){ 
  function format_uang($nilai){
    if((int)$nilai==0){
      $nilai='0';
    }else{
      $nilai=number_format($nilai, 0, ',', '.');
    }
            return $nilai;
  }  
} 
function tanggal_indon($tanggal, $cetak_hari = false){
    $hari = array ( 1 =>    'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu');
    $bulan = array (1 =>   'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember');
    $split    = explode('-', $tanggal);
    $tgl_indo = $split[2] . ' ' . $bulan[ (int)$split[1] ] . ' ' . $split[0];
    
    if ($cetak_hari) {
        $num = date('N', strtotime($tanggal));
        return $hari[$num] . ', ' . $tgl_indo;
    }
    return $tgl_indo;
}
function arr2md5($arrinput){ $hasil=''; foreach($arrinput as $val){ if($hasil==''){ $hasil=md5($val); } else { $code=md5($val); for($hit=0;$hit<min(array(strlen($code),strlen($hasil)));$hit++){ $hasil[$hit]=chr(ord($hasil[$hit]) ^ ord($code[$hit])); } } } return(md5($hasil)); } function getPassword($pase){ $pass = arr2md5($pase); return $pass; }
function kukurl($url, $datanya){
  $ch = curl_init(); 
  curl_setopt($ch, CURLOPT_URL, $url);
  curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
  curl_setopt($ch, CURLOPT_POSTFIELDS, $datanya);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
  $output = curl_exec($ch); 
  curl_close($ch);      
  return $output;
}

function curl($url, $data){
    $ch = curl_init(); 
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
    $output = curl_exec($ch); 
    curl_close($ch);      
    return $output;
}

if (!function_exists('kasuari_sanitize_rich_node')) {
  function kasuari_sanitize_rich_node($parent, $allowedTags, $blockedTags) {
    $children = array();
    foreach ($parent->childNodes as $child) {
      $children[] = $child;
    }

    foreach ($children as $child) {
      if ($child->nodeType === XML_COMMENT_NODE) {
        $parent->removeChild($child);
        continue;
      }

      if ($child->nodeType !== XML_ELEMENT_NODE) {
        continue;
      }

      $tagName = strtolower($child->nodeName);
      if (in_array($tagName, $blockedTags, true)) {
        $parent->removeChild($child);
        continue;
      }

      kasuari_sanitize_rich_node($child, $allowedTags, $blockedTags);

      if (!in_array($tagName, $allowedTags, true)) {
        while ($child->firstChild) {
          $parent->insertBefore($child->firstChild, $child);
        }
        $parent->removeChild($child);
        continue;
      }

      while ($child->attributes && $child->attributes->length > 0) {
        $child->removeAttributeNode($child->attributes->item(0));
      }
    }
  }
}

if (!function_exists('kasuari_safe_rich_text')) {
  function kasuari_safe_rich_text($value, $fallback = '-') {
    $value = trim((string) $value);
    if ($value === '') {
      return '<p class="ks-rich-empty">' . htmlspecialchars($fallback, ENT_QUOTES, 'UTF-8') . '</p>';
    }

    $value = html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    if (!class_exists('DOMDocument')) {
      return nl2br(htmlspecialchars(strip_tags($value), ENT_QUOTES, 'UTF-8'));
    }

    $allowedTags = array(
      'p', 'br', 'ol', 'ul', 'li', 'strong', 'b', 'em', 'i', 'u',
      'blockquote', 'div', 'span', 'hr', 'sup', 'sub',
      'table', 'thead', 'tbody', 'tfoot', 'tr', 'th', 'td',
      'h1', 'h2', 'h3', 'h4', 'h5', 'h6'
    );
    $blockedTags = array(
      'script', 'style', 'iframe', 'object', 'embed', 'svg', 'math',
      'form', 'input', 'button', 'textarea', 'select', 'option',
      'link', 'meta', 'base'
    );

    $document = new DOMDocument('1.0', 'UTF-8');
    $previousErrors = libxml_use_internal_errors(true);
    $loaded = $document->loadHTML(
      '<?xml encoding="utf-8" ?><div id="kasuari-rich-root">' . $value . '</div>',
      LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
    );
    libxml_clear_errors();
    libxml_use_internal_errors($previousErrors);

    if (!$loaded) {
      return nl2br(htmlspecialchars(strip_tags($value), ENT_QUOTES, 'UTF-8'));
    }

    $root = $document->getElementById('kasuari-rich-root');
    if (!$root) {
      return nl2br(htmlspecialchars(strip_tags($value), ENT_QUOTES, 'UTF-8'));
    }

    kasuari_sanitize_rich_node($root, $allowedTags, $blockedTags);
    $output = '';
    foreach ($root->childNodes as $child) {
      $output .= $document->saveHTML($child);
    }

    return trim($output) !== ''
      ? $output
      : '<p class="ks-rich-empty">' . htmlspecialchars($fallback, ENT_QUOTES, 'UTF-8') . '</p>';
  }
}
?>

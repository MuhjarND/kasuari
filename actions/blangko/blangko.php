 <?php 
	function get_blangko_tree($koneksi,$parent_id){
		$menu="";
		$sqlquery=" SELECT * FROM jenis_blangko where jenis_blangko_parent_id='".$parent_id."' order by urutan asc, jenis_blangko_id asc "; 
		//echo $sqlquery."<br>";
		
		$query=mysqli_query($koneksi,$sqlquery);
		while($row=mysqli_fetch_assoc($query)){
		$menu.='<button onclick="buka_daftar('.$row['jenis_blangko_id'].')" class="w3-padding-8 w3-button w3-border-bottom w3-block w3-left-align w3-dark-grey">'.$row['jenis_blangko_nama'].'</button>
						 
				<div id="'.$row['jenis_blangko_id'].'" class="w3-border w3-hide">
  					<div class="w3-container"><ul class="w3-ul">
						';
				$sqlquery1=" SELECT * FROM template_dokumen where jenis_blangko_id=".$row['jenis_blangko_id']."  order by nama asc "; 
				//echo $sqlquery1."<br>";
				$res1=mysqli_query($koneksi,$sqlquery1);
				while($row1=mysqli_fetch_assoc($res1)){
					$tambahan=$row1['nama'] ;
					$menu.= "<li><a href=# onclick=edit_blangko(".$row1['id'].")><span>  ".$row1['nama']." </span></a> </li>";
					 
				}
				$menu.="</ul></div></div>";		
				
		$menu.="";
		}

		return $menu;
	}
?>

<?php
echo '<div class="w3-responsive">';
echo get_blangko_tree($koneksi,0);
echo "</div>";
mysqli_close($koneksi);
?>
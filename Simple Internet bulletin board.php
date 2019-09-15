<?php
$dsn = 'データベース名';
$user = 'ユーザ名';
$password = 'パスワード';
$pdo = new PDO($dsn, $user, $password, array(PDO::ATTR_ERRMODE => PDO::ERRMODE_WARNING));

$sql = "CREATE TABLE IF NOT EXISTS tbPost" //掲示板用テーブルを作成
." ("
. "id INT AUTO_INCREMENT PRIMARY KEY," //AUTO_INCREMENTは自動で番号を振り分ける
. "name char(32),"
. "comment TEXT,"
. "uptime DATETIME,"
. "pass TEXT"
.");";
$stmt = $pdo -> query($sql);

$editnum = "";
$editname = "";
$editcomment = "";
$editpass2 = "";
$form = "";
$message = "";

if(isset($_POST['send'])){ //送信ボタンが押されたら以下の処理を実行
    $name = $_POST['name'];
    $comment = $_POST['comment'];
    $uptime = date('Y-m-d H:i:s');
    $pass = $_POST['pass'];
    if($comment === "" or $name === ""){ //文字が入力されなかったとき
        $alert = "<script type='text/javascript'>alert('名前とコメントを入力してから送信してください。');</script>";
	echo $alert; //JavaScriptのアラート表示
    }
    elseif(!empty($comment) && !empty($name) && empty($_POST['bnumber'])){ //!emptyによって文字が入っている場合はtrue
        $message = "-メッセージ- <br/>". $name. "さん ｢". $comment. "｣ を受け付けました。<br/>パスワードは削除、編集の際に必要です。";
	$sql = $pdo -> prepare("INSERT INTO tbPost (name, comment, uptime, pass) VALUES (:name, :comment, '$uptime',:pass)");
        $sql -> bindParam(':name', $name, PDO::PARAM_STR);
        $sql -> bindParam(':comment', $comment, PDO::PARAM_STR);
        $sql -> bindValue(':pass', $pass, PDO::PARAM_STR);
        $sql -> execute();	
    }
}

if(isset($_POST['edit'])){ //編集ボタンが押されたら以下の処理を実行
    $editnumber = $_POST['editnumber'];
    $editpass = $_POST['editpass'];
    if(!empty($editnumber) && is_numeric($editnumber)){
        $sql = "SELECT * FROM tbPost";
        $stmt = $pdo->query($sql);
        $result = $stmt->fetchAll();
        foreach($result as $row){
            if($editnumber == $row['id'] && $editpass == $row['pass']){
                $editnum = $row['id'];
		$editname = $row['name'];
		$editcomment = $row['comment'];
                $editpass2 = $row['pass'];
                
                $form = "-メッセージ- <br/> [No.". $editnumber. "] の書き込みを編集してください。<br/>送信ボタンを押すと上書きされます。";
                
            }
            
        }
    }
    elseif($number === ""){
	$alert = "<script type='text/javascript'>alert('投稿番号を入力してから削除ボタンを押してください。');</script>";
	echo $alert; //JavaScriptのアラート表示
    }
    else{
        $alert = "<script type='text/javascript'>alert('半角数字で入力してください。');</script>";
        echo $alert; //JavaScriptのアラート表示
    }
}

if(!empty($_POST['comment']) && !empty($_POST['name']) && !empty($_POST['bnumber'])){ //投稿上書き処理
    $id = $_POST['bnumber'];
    $name = $_POST['name'];
    $comment = $_POST['comment'];
    $uptime = date('Y-m-d H:i:s');
    $pass = $_POST['pass'];
    
    $sql = "update tbPost set name=:name, comment=:comment, uptime=:uptime, pass=:pass where id=:id";
    $stmt = $pdo -> prepare($sql);
    $stmt->bindParam(':name',$name,PDO::PARAM_STR);
    $stmt->bindParam(':comment',$comment,PDO::PARAM_STR);
    $stmt->bindParam(':uptime',$uptime,PDO::PARAM_STR);
    $stmt->bindParam(':pass',$pass,PDO::PARAM_STR);
    $stmt->bindParam(':id',$id,PDO::PARAM_INT);
    $stmt->execute();
    
    $message = "-メッセージ- <br/> [No.". $id. "] の書き込みを上書きしました。";
}

if(isset($_POST['delete'])){ //削除ボタンが押されたら以下の処理を実行
    $number = $_POST['number'];
    $delpass = $_POST['delpass'];
    if(!empty($number) && is_numeric($number)){
        $sql = "SELECT * FROM tbPost";
        $stmt = $pdo->query($sql);
        $result = $stmt->fetchAll();
        foreach($result as $row){
            if($number == $row['id'] && $delpass == $row['pass']){
                $sql = "DELETE FROM tbPost where id = :id"; //DELETEでidカラムを選択
                $stmt = $pdo->prepare($sql);
                $delete = array(':id' => $number);
                $stmt -> execute($delete);
        
                $sql = "ALTER TABLE tbPost DROP COLUMN id"; //投稿番号振り直し
                $stmt = $pdo->prepare($sql);
                $stmt -> execute();
                $sql = "ALTER TABLE tbPost ADD id INT PRIMARY KEY NOT NULL AUTO_INCREMENT FIRST";
                $stmt = $pdo->prepare($sql);
                $stmt -> execute();
        
                $message =  "-メッセージ- <br/> [No.". $number. "]の投稿を削除しました。";
            }
        }
    }
    elseif($number === ""){
	$alert = "<script type='text/javascript'>alert('投稿番号を入力してから削除ボタンを押してください。');</script>";
	echo $alert; //JavaScriptのアラート表示
    }
    else{
        $alert = "<script type='text/javascript'>alert('半角数字で入力してください。');</script>";
        echo $alert; //JavaScriptのアラート表示
    }
}

class Post{
    public function display(){
        global $pdo; //クラス内でグローバル変数を使うために必要
        $sql = "SELECT * FROM tbPost";
        $stmt = $pdo -> query($sql);
            foreach ($stmt as $row){
                echo '[No.'. $row['id']. '] ';
                echo '<strong>'. htmlspecialchars($row['name'], ENT_QUOTES). '</strong> ';
                echo $row['uptime']. '<br/>';
                echo nl2br(htmlspecialchars($row['comment'], ENT_QUOTES)). '<br>';
            }
    }
}
$Post = new Post();

if(isset($_POST['zensakujo'])){
    if($_POST['kanripass'] == "管理パスワード"){
        $sql = "TRUNCATE TABLE tbPost";
        $stmt = $pdo->query($sql);
        echo "-管理者へ- 掲示板の投稿をすべて削除しました。";
    }
    elseif($_POST['kanripass'] == "管理パスワード"){
        $sql = "DROP TABLE IF EXISTS tbPost";
        $stmt = $pdo->query($sql);
        echo "-管理者へ- テーブルを削除しました";
    }
    else{
        $alert = "<script type='text/javascript'>alert('パスワードが違います。');</script>";
        echo $alert;
    }
}

?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8"> <!- //文字コード指定 ->
    <title>けいじばん</title>
</head>
<body>
    <h1>簡易掲示板</h1>
        <hr width="100%">
    <h3>投稿フォーム</h3>
        <?php echo $form; ?>
	<form action = "" method = "post"> <!- //actionで指定したファイルの実行 methodは取得方法 ->
            <div>
                <label for = "name">名前</label>
                <input type = "text" name = "name" placeholder = "名前" value="<?php echo $editname; ?>">
            </div>
            <div>
                <label for = "pass">パスワード</label>
                <input type = "password" name = "pass" size = "8" minlength = "4" maxlength = "10" autocomplete = "off" required value="<?php echo $editpass2; ?>"><br/>
                ※<font size="1" color="red">パスワードは半角英数4文字以上10文字まで設定可能です。</font>
            </div>
            <div>
		<label for = "comment">コメント</label>
            </div>
            <div>
		<textarea name="comment" cols="40" rows="5" placeholder ="コメントを入力してください"><?php echo $editcomment; ?></textarea> <!- //コメント入力欄作成 ->
		<input type = "submit" name = "send" value = "送信"> <!- //送信ボタン作成 ->
                <input type="hidden" name="bnumber" value="<?php echo $editnum;?>">
            </div>
	</form>
	<hr width="100%">
           <?php echo $message; ?>
        <hr width="100%">
    <h3>掲示板</h3>
	-投稿番号、名前、投稿･更新日時、コメントが表示されます- <br>
        <?php $Post->display(); ?>
        <hr width="100%">
    <form action = "" method = "post">
	<div>
            投稿編集 <br/>
            <label for = "editnumber">編集する投稿番号</label>
            <input type = "text" name = "editnumber" size= "4" placeholder = "投稿番号">
        </div>       
        <div>
            <label for = "editpass">パスワード</label>
            <input type = "password" name = "editpass" size = "8" maxlength = "10" autocomplete = "off" required>
            <input type = "submit" name = "edit" value = "編集"> 
        </div>
    </form>
    <hr width="100%">
    <form action = "" method = "post">
	<div>
            投稿削除 <br/>
            <label for = "number">削除する投稿番号</label>
            <input type = "text" name = "number" size= "4" placeholder = "投稿番号">
             
	</div>
        <div>
            <label for = "delpass">パスワード</label>
            <input type = "password" name = "delpass" size = "8" maxlength = "10" autocomplete = "off" required>
            <input type = "submit" name = "delete" value = "削除">
        </div>
    </form>
     <hr width="100%">
    <form action = "" method = "post">
	<font size="2" color="black">※管理者用<br/>
        <div>
            <label for = "kanripass">管理者パスワード</label>
            <input type = "password" name = "kanripass" size = "4" maxlength = "4" autocomplete = "off" required>
            <input type = "submit" name = "zensakujo" value = "入力">
        </div></font>
    </form>

</body>
</html>
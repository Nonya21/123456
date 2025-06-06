<?php
$servername = "localhost";// 這是資料庫伺服器的位置，通常 "localhost" 代表就在自己這台電腦上。
$username = "root";// 這是登入資料庫用的帳號，這裡用的是 "root"（預設管理員）。
$password = ""; // 這是登入資料庫用的密碼，這裡是空的，代表沒有設定密碼。
$dbname = "social_network";// 這是要連接的資料庫名稱，叫做 "social_network"。

// 建立一個新的資料庫連線
$conn = new mysqli($servername, $username, $password, $dbname);
// 如果連線失敗，就顯示錯誤訊息
if ($conn->connect_error) die("資料庫連接失敗: " . $conn->connect_error);
// 啟動 session，這樣可以記住使用者的登入狀態或其他資料
session_start();

   // 如果有收到 POST 請求，且裡面有 'set_music_id' 這個欄位（代表用戶有傳送音樂 ID 過來）
if (isset($_POST['set_music_id'])) {

    // 設定一個名為 'music_id' 的 cookie
    // intval($_POST['set_music_id'])：把用戶傳來的音樂 ID 轉成整數，避免非法字串
    // time() + 3600 * 24 * 30：設定這個 cookie 的有效期限為 30 天（3600 秒 * 24 小時 * 30 天）
    // '/'：這個 cookie 在網站所有路徑都有效
    setcookie('music_id', intval($_POST['set_music_id']), time() + 3600 * 24 * 30, '/');
     exit; // 結束程式，不再執行後面的程式碼
}

  // 如果 POST 請求裡有 'ajax_action' 這個欄位（通常是前端用 AJAX 傳來的資料）
if (isset($_POST['ajax_action'])) {
    header('Content-Type: application/json');  // 設定回應的內容格式為 JSON，讓前端知道這是 JSON 格式的資料
    $response = ['success' => false];  // 建立一個名叫 $response 的陣列，裡面有一個 'success' 欄位，預設值是 false
                                       // 這通常用來回傳給前端，表示目前動作還沒成功

    // 如果收到的 POST 請求中 'ajax_action' 是 'new_post'，而且使用者已經登入（$_SESSION["user"] 有值）
    if ($_POST['ajax_action'] === 'new_post' && isset($_SESSION["user"])) {
        $title = $conn->real_escape_string($_POST["title"]);  // 從 POST 請求中取得 'title'，並用 real_escape_string 避免 SQL 注入
        $content = $conn->real_escape_string($_POST["content"]);   // 從 POST 請求中取得 'content'，同樣做安全處理
        $authorUID = $_SESSION["user"]["UID"];   // 從登入的 session 取得作者的 UID（使用者的唯一編號）

        // 執行 SQL 指令，把新文章的標題、內容、作者 UID 存進資料庫的 Post 表格
        $conn->query("INSERT INTO Post (Title, Content, AuthorUID) VALUES ('$title', '$content', $authorUID)"); 
        $postID = $conn->insert_id;    // 取得剛剛新增的這篇文章在資料庫中的自動編號（主鍵）

         // 檢查是否有上傳名為 'post_images' 的檔案
        if (isset($_FILES['post_images'])) {
            $files = $_FILES['post_images'];    // 把上傳的檔案資訊存到 $files 變數裡
            $allowedTypes = ["jpg", "jpeg", "png", "gif"]; // 設定一個陣列，裡面存放允許上傳的圖片副檔名
            $targetDir = "uploads/";  // 指定上傳檔案要存放的資料夾名稱為 uploads

            if (!is_dir($targetDir)) mkdir($targetDir, 0777, true); // 如果 uploads 資料夾不存在，就建立這個資料夾，權限設為 0777（所有人都可讀寫執行）
                                                                    // true 代表如果有多層資料夾不存在，也會一併建立

            // 這個 for 迴圈會從 0 開始，重複執行，直到所有上傳的檔案都處理完畢
            // $files['name'] 是一個陣列，裡面存放了所有檔案的名稱
            // count($files['name']) 會計算總共有幾個檔案
            for ($i = 0; $i < count($files['name']); $i++) {
                if ($files['error'][$i] === 0) {    // 檢查這個檔案在上傳時有沒有發生錯誤
                                                    // $files['error'][$i] 代表第 $i 個檔案的錯誤代碼
                                                    // 如果等於 0，表示這個檔案上傳成功，沒有錯誤

                    $fileName = uniqid() . "_" . basename($files['name'][$i]);  // 產生一個唯一的檔案名稱，避免檔案名稱重複被覆蓋
                                                                                // uniqid() 會產生一個獨一無二的字串
                                                                                // basename($files['name'][$i]) 會取得原始檔案名稱（不包含路徑）
                                                                                // 兩者用底線 _ 連接起來

                    $targetFilePath = $targetDir . $fileName;    // 把目標資料夾路徑（$targetDir）和新的檔案名稱（$fileName）合併起來
                                                                 // 得到這個檔案要儲存的完整路徑

                    $fileType = strtolower(pathinfo($targetFilePath, PATHINFO_EXTENSION)); // 取得這個檔案的副檔名（例如 jpg、png、pdf）
                                                                                           // pathinfo(..., PATHINFO_EXTENSION) 會抓出副檔名
                                                                                           // strtolower() 會把副檔名轉成小寫，方便後續比對檔案類型

                    if (in_array($fileType, $allowedTypes)) {// 檢查目前這個檔案的類型（$fileType）是不是在允許的類型清單（$allowedTypes）裡面。
                                                             // 如果是，才繼續執行下面的程式碼。

                         // 將使用者上傳的臨時檔案（$files['tmp_name'][$i]）移動到指定的目標路徑（$targetFilePath）。                                                              
                         // 如果移動成功，才會執行下面的動作。
                        if (move_uploaded_file($files['tmp_name'][$i], $targetFilePath)) { 
                            // 在資料庫裡找到對應的貼文（PostID = $postID），                                                                                                               
                            // 然後把這篇貼文的內容（Content）加上一段新的圖片標記：                                                                                                                    
                            // 例如：\n[IMG]檔案名稱[/IMG]                                                                              
                            // 這樣之後顯示貼文時，就會看到有插入這張圖片。
                            $conn->query("UPDATE Post SET Content = CONCAT(Content, '\n[IMG]', '$fileName', '[/IMG]') WHERE PostID = $postID");   
                        }
                    }
                }
            }
        }

        $response['success'] = true;//在 $response 這個陣列裡面，新增一個叫做 "success" 的欄位，並且把它的值設定為 true，通常用來表示某個操作已經成功完成。
    }
    // 檢查是否有從前端送來 'like_post' 的 AJAX 請求，並且使用者已經登入（SESSION 裡有 user）
    if ($_POST['ajax_action'] === 'like_post' && isset($_SESSION["user"])) {
        $postID = intval($_POST["post_id"]);  // 從前端取得要按讚的貼文 ID，並轉成整數，避免 SQL 注入
        $userUID = $_SESSION["user"]["UID"];  // 從 SESSION 取得目前登入的使用者 UID
        $checkLike = $conn->query("SELECT * FROM `Like` WHERE PostID = $postID AND UserUID = $userUID"); // 查詢資料庫，檢查這個使用者是否已經對這篇貼文按過讚
        if ($checkLike->num_rows > 0) { // 如果查詢結果有資料，代表已經按過讚
            $conn->query("DELETE FROM `Like` WHERE PostID = $postID AND UserUID = $userUID"); // 已經按過讚，再按一次代表要取消讚，所以從資料庫把這筆讚的紀錄刪除
        } else {
            $conn->query("INSERT INTO `Like` (PostID, UserUID) VALUES ($postID, $userUID)"); // 沒有按過讚，這次是要按讚，所以新增一筆按讚紀錄到資料庫
        }
        // 查詢資料庫，計算指定貼文(PostID)的按讚數量
        $likeCount = $conn->query("SELECT COUNT(*) AS cnt FROM `Like` WHERE PostID = $postID")->fetch_assoc()['cnt'];
        $response['success'] = true;// 設定回傳資料的 success 欄位為 true，表示操作成功
        $response['likeCount'] = $likeCount;// 把剛剛查詢到的按讚數量放進回傳資料的 likeCount 欄位
    }
    // 檢查是否收到 ajax_action=delete_post 的請求，且使用者已登入
    if ($_POST['ajax_action'] === 'delete_post' && isset($_SESSION["user"])) {
        $postID = intval($_POST["post_id"]); // 取得要刪除的貼文 ID，並轉成整數
        $user = $_SESSION["user"];  // 取得目前登入的使用者資訊
        $sql = "SELECT * FROM Post WHERE PostID = $postID"; // 查詢資料庫，看看這個貼文是否存在
        $result = $conn->query($sql);
         // 如果有找到這篇貼文
        if ($result->num_rows > 0) {
            $postRow = $result->fetch_assoc(); // 取得這篇貼文的資料
            $isAuthor = $postRow['AuthorUID'] == $user['UID'];  // 檢查目前使用者是不是這篇貼文的作者
            $isAdminOrHelper = in_array($user['Role'], ['Admin', 'Helper']); // 檢查目前使用者是不是管理員或小幫手
            // 如果是作者，或是管理員/小幫手，就允許刪除
            if ($isAuthor || $isAdminOrHelper) {
                $conn->query("DELETE FROM Post WHERE PostID = $postID"); // 執行刪除貼文的 SQL 指令
                $response['success'] = true; // 設定回傳結果為成功
            }
        }
    }
    // 判斷是否為「編輯貼文」的 AJAX 請求，且使用者已登入
    if ($_POST['ajax_action'] === 'edit_post' && isset($_SESSION["user"])) {
        $postID = intval($_POST["post_id"]); // 取得要編輯的貼文 ID，並確保是整數（避免 SQL 注入）
        $newTitle = $conn->real_escape_string($_POST["new_title"]); // 取得新的標題，並用資料庫的 escape function 避免特殊字元導致 SQL 注入
        $newContent = $conn->real_escape_string($_POST["new_content"]); // 取得新的內文，同樣做字元過濾
        $user = $_SESSION["user"];  // 從 session 取得目前登入的使用者資料
        $sql = "SELECT * FROM Post WHERE PostID = $postID"; // 查詢資料庫，看這篇貼文是否存在
        $result = $conn->query($sql);
         // 如果查詢結果有資料（代表貼文存在）
        if ($result->num_rows > 0) {
            $postRow = $result->fetch_assoc();   // 取得這篇貼文的所有資料（轉成關聯陣列）
            $isAuthor = $postRow['AuthorUID'] == $user['UID'];// 檢查目前登入的使用者是不是這篇貼文的作者
            // 如果是作者本人
            if ($isAuthor) {
                $conn->query("UPDATE Post SET Title = '$newTitle', Content = '$newContent' WHERE PostID = $postID");// 更新這篇貼文的標題和內容（圖片不變）
                $response['success'] = true; // 回應成功
            }
        }
    }
    //檢查是否是 AJAX 請求且使用者已登入
    if ($_POST['ajax_action'] === 'new_comment' && isset($_SESSION["user"])) {
        $postID = intval($_POST["post_id"]);// 取得文章ID，並轉成整數型態，避免SQL注入
        $content = $conn->real_escape_string($_POST["comment_content"]); // 取得留言內容，並用 real_escape_string 處理，防止SQL注入
        $userUID = $_SESSION["user"]["UID"]; // 取得目前登入使用者的UID
        $conn->query("INSERT INTO Comment (Content, PostID, UserUID) VALUES ('$content', $postID, $userUID)");  // 新增留言到 Comment(評論) 資料表
        $commentID = $conn->insert_id;   // 取得剛剛新增留言的 CommentID（評論 主鍵ID）
        // 檢查有沒有上傳圖片
        if (isset($_FILES['comment_images'])) {
            $files = $_FILES['comment_images']; // 取得圖片檔案資訊
            $allowedTypes = ["jpg", "jpeg", "png", "gif"]; // 允許的圖片副檔名
            $targetDir = "uploads/";// 上傳目錄
            // 如果 uploads 目錄不存在就建立它
            if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);
            // 處理每一張上傳的圖片
            for ($i = 0; $i < count($files['name']); $i++) {
                // 檔案上傳沒發生錯誤才處理
                if ($files['error'][$i] === 0) {
                    $fileName = uniqid() . "_" . basename($files['name'][$i]); // 產生唯一檔名，避免覆蓋
                    $targetFilePath = $targetDir . $fileName; 
                    $fileType = strtolower(pathinfo($targetFilePath, PATHINFO_EXTENSION)); // 取得副檔名，並轉小寫
                    // 檢查副檔名是否合法
                    if (in_array($fileType, $allowedTypes)) {
                        // 移動上傳檔案到指定目錄
                        if (move_uploaded_file($files['tmp_name'][$i], $targetFilePath)) {
                             // 把圖片標籤插入到留言內容裡（用[IMG]...[/IMG]包住檔名）
                            $conn->query("UPDATE Comment SET Content = CONCAT(Content, '\n[IMG]', '$fileName', '[/IMG]') WHERE CommentID = $commentID");
                        }
                    }
                }
            }
        }

        $response['success'] = true;  // 回傳成功訊息
    }
    
    // 檢查這是一個刪除留言的 AJAX 請求，且使用者已登入
    if ($_POST['ajax_action'] === 'delete_comment' && isset($_SESSION["user"])) {
        $commentID = intval($_POST["comment_id"]);  // 取得要刪除的留言ID，並轉成整數（防止SQL注入）
        $currentUser = $_SESSION["user"]; // 取得目前登入的使用者資料
        
        // 從資料庫查詢這則留言的詳細資料（包含留言者身分、貼文作者ID等）
        $commentRow = $conn->query("SELECT Comment.*, User.Role AS CommentUserRole, Post.AuthorUID AS PostAuthorUID, User.UID AS CommentUserUID 
            FROM Comment --從評論--
            JOIN User ON Comment.UserUID = User.UID   --加入用戶 評論.用戶uid=用戶uid--
            JOIN Post ON Comment.PostID = Post.PostID  --加入貼文 評論.貼文uid=貼文.貼文uid--
            WHERE Comment.CommentID = $commentID")->fetch_assoc();
        $canDelete = false;// 預設不能刪除
        
        // 如果查到這則留言
        if ($commentRow) {
            $isAdminOrHelper = in_array($currentUser["Role"], ["Admin", "Helper"]);// 判斷目前登入的使用者是否為 Admin 或 Helper
            $isPostAuthor = $commentRow["PostAuthorUID"] == $currentUser["UID"]; // 判斷目前使用者是否為該貼文的作者
            $isCommentAuthor = $commentRow["CommentUserUID"] == $currentUser["UID"];// 判斷目前使用者是否為該留言的作者
            $isCommentAdminOrHelper = in_array($commentRow["CommentUserRole"], ["Admin", "Helper"]); // 判斷留言者是否為 Admin 或 Helper
            
            // 如果目前登入的使用者是 Admin 或 Helper，可以刪除
            if ($isAdminOrHelper) {
                $canDelete = true;
             // 如果留言者是 Admin 或 Helper，其他人不能刪除
            } elseif ($isCommentAdminOrHelper) {
                $canDelete = false;
             // 如果是貼文作者或留言作者本人，可以刪除
            } elseif ($isPostAuthor || $isCommentAuthor) {
                $canDelete = true;
            }
        }
         // 如果有刪除權限
        if ($canDelete) {
            $conn->query("DELETE FROM Comment WHERE CommentID = $commentID");// 從資料庫刪除這則留言
            $response['success'] = true; // 回傳成功訊息
        }
    }
    // 取得留言區塊HTML
    // 檢查這是一個取得留言的 AJAX 請求，且使用者已登入
    if ($_POST['ajax_action'] === 'get_comments' && isset($_SESSION["user"])) {
        ob_start();  // 啟動 PHP 的輸出緩衝區，將後續產生的 HTML 存起來，最後可以一次傳回
        $postID = intval($_POST["post_id"]);// 取得要查詢留言的貼文ID，並轉成整數型別（防止SQL注入）
        $post = $conn->query("SELECT * FROM Post WHERE PostID=$postID")->fetch_assoc(); // 從資料庫取得這篇貼文的所有資料
        
        // 準備查詢語句：取得這篇貼文下所有留言，並且取得留言者的暱稱、身分、UID、頭像
        //Comment.*                    // 取得留言所有欄位
        //User.Nickname                // 取得留言者暱稱
        //User.Role AS CommentUserRole // 取得留言者身分（角色）
        //User.UID AS CommentUserUID   // 取得留言者UID
        //User.Avatar                  // 取得留言者頭像
        //JOIN User ON Comment.UserUID = User.UID // 用留言者的UID把留言和使用者資料連接起來
        //WHERE Comment.PostID = $postID          // 只查詢這篇貼文下的留言
        //ORDER BY Comment.CreatedAt ASC          // 按留言時間（由舊到新）排序
        $commentQuery = "SELECT Comment.*, User.Nickname, User.Role AS CommentUserRole, User.UID AS CommentUserUID, User.Avatar FROM Comment JOIN User ON Comment.UserUID = User.UID WHERE Comment.PostID = $postID ORDER BY Comment.CreatedAt ASC";
        $commentResult = $conn->query($commentQuery); // 執行查詢語句，取得所有留言資料
        ?>
        <!-- 外層留言區塊 -->
        <div class='comment-box'>
            <h3>留言</h3>
            <?php
            // 迴圈顯示每一則留言，$commentResult 是留言查詢結果
            while ($comment = $commentResult->fetch_assoc()) {
                $canDeleteComment = false; // 預設不能刪除留言
                $currentUser = $_SESSION["user"];  // 取得目前登入的使用者資料
                $isAdminOrHelper = in_array($currentUser["Role"], ["Admin", "Helper"]);  // 判斷目前使用者是否為管理員或小幫手
                $isPostAuthor = $post["AuthorUID"] == $currentUser["UID"]; // 判斷目前使用者是否為貼文作者
                $isCommentAuthor = $comment["CommentUserUID"] == $currentUser["UID"];// 判斷目前使用者是否為留言作者
                $isCommentAdminOrHelper = in_array($comment["CommentUserRole"], ["Admin", "Helper"]);// 判斷這則留言的作者是否為管理員或小幫手
                // 權限判斷：管理員/小幫手可刪
                if ($isAdminOrHelper) {
                    $canDeleteComment = true;
                //留言是管理員/小幫手則不能被其他人刪
                } elseif ($isCommentAdminOrHelper) {
                    $canDeleteComment = false;
                //貼文作者或留言作者本人可刪
                } elseif ($isPostAuthor || $isCommentAuthor) {
                    $canDeleteComment = true;
                }
                echo "<div class='comment-inner'>";  // 單一留言外框
                $avatarPath = $comment["Avatar"] ? "uploads/" . $comment["Avatar"] : "default-avatar.png";  // 處理頭像路徑，如果沒有頭像用預設圖
                
                // 顯示頭像圖片，點擊可以到個人頁面
                // img 樣式說明：
                // width: 40px; height: 40px;  // 頭像大小(寬高)
                // border-radius: 50%;         // 圓形頭像
                // margin-right: 8px;          // 右邊留空
                echo "<a href='index.php?profile_uid=" . $comment["CommentUserUID"] . "'><img src='" . htmlspecialchars($avatarPath) . "' alt='頭像' style='width:40px;height:40px;border-radius:50%;margin-right:8px;'></a>";
                echo "<div class='comment-content-main'>";// 留言內容主體區
                echo "<strong>" . htmlspecialchars($comment["Nickname"]) . "</strong> ";// 顯示暱稱與留言時間
                echo "<small style='color:#888;'>(" . $comment["CreatedAt"] . ")</small><br>";// 輸出留言的建立時間，並以灰色小字顯示在網頁上，後面換行
                $content = nl2br(htmlspecialchars($comment["Content"]));// 將 $comment["Content"] 的內容進行 HTML 字元轉換，並將換行符號轉換為 <br> 標籤，最後存入 $content 變數
                $img_index = 0;// 宣告並初始化變數 $img_index，設為 0

                // 使用 preg_replace_callback 來尋找所有 [IMG]...[/IMG] 標籤，並對每個找到的內容執行回呼函式
                // 正規表達式，匹配 [IMG] 和 [/IMG] 之間的內容（非貪婪模式）
                // 回呼函式，$matches 包含匹配到的內容，&$img_index 以引用方式使用外部變數
                $content = preg_replace_callback('/\[IMG\](.*?)\[\/IMG\]/', function($matches) use (&$img_index) {
                    
                    // 將 $matches[1] 的內容經過 htmlspecialchars 處理（防止 XSS 攻擊），
                    // 並與 "uploads/" 字串結合，產生圖片的完整路徑，存入 $imgPath 變數中。
                    $imgPath = "uploads/" . htmlspecialchars($matches[1]);

                    // 建立一個 HTML <img> 標籤，將其存入 $img_html 變數中
                    // src='$imgPath'  //設定圖片來源路徑，使用變數 $imgPath
                    //class='comment-img-preview'  //設定圖片的 CSS 類別為 comment-img-preview
                    // 內嵌樣式：設定寬高為 80px，圖片覆蓋填滿區域，左邊距 10px，圓角 5px，垂直置中，滑鼠懸停時顯示為可點擊
                    //alt='留言圖片'   // 替代文字，當圖片無法顯示時顯示「留言圖片」
                    // onclick='showImageModal(\"$imgPath\")'  //當點擊圖片時，呼叫 showImageModal 函式，並傳入 $imgPath 作為參數
                    $img_html = "<img src='$imgPath' class='comment-img-preview' style='width:80px;height:80px;object-fit:cover;margin-left:10px;border-radius:5px;vertical-align:middle;cursor:pointer;' alt='留言圖片' onclick='showImageModal(\"$imgPath\")'>";
                    $img_index++;// 將 $img_index 變數的值加 1（自增 1）
                    
                    // 每三張圖片一排
                    // 如果 $img_index 除以 3 的餘數為 1，則表示這是每行開頭的第一張圖片
                    // 在這種情況下，將 $img_html 包在 <div class='comment-img-row'> 標籤內，開始一個新的圖片列
                    if ($img_index % 3 == 1) $img_html = "<div class='comment-img-row'>$img_html";
                    // 如果 $img_index 能被 3 整除，代表已經顯示了三張圖片，則結束當前的 <div> 區塊
                    if ($img_index % 3 == 0) $img_html .= "</div>";
                    return $img_html; // 將產生的圖片 HTML 片段回傳（通常用於 preg_replace_callback 的回呼函式內）
                }, $content); // 這是匿名函式的結尾，並將 $content 作為處理目標（通常用於 preg_replace_callback）
                // 如果最後一排不滿三張，要補上結尾的 </div>
                if ($img_index % 3 != 0 && $img_index > 0) $content .= "</div>";
                echo $content;// 顯示留言內容（含圖片）
                echo "</div>";// 結束留言內容主體區

                 // 如果有刪除權限，顯示刪除按鈕
                 // 如果使用者有刪除留言的權限
                 if ($canDeleteComment) {
                 // 輸出一個表單，當提交時會呼叫 deleteComment JavaScript 函數，並傳入留言的 ID、表單本身和事件物件
                 echo "<form onsubmit='deleteComment(".$comment["CommentID"].",this,event)' style='margin:0;'>";
    
                 // 隱藏欄位，儲存該留言的 ID，方便後端辨識要刪除哪一則留言
                 echo "<input type='hidden' name='comment_id' value='" . $comment["CommentID"] . "'>";
    
                 // 刪除按鈕，點擊後會提交表單（觸發 onsubmit 事件），顯示為一個 "X"，並有提示文字「刪除留言」
                 echo "<button type='submit' class='comment-delete-btn' title='刪除留言'>X</button>";
    
                 // 關閉表單標籤
                 echo "</form>";
                }
                echo "</div>"; // 結束單一留言外框
            }
            ?>
        </div>
        <!-- 建立一個留言表單，送出時會呼叫 submitCommentForm(this, event) 這個 JavaScript 函式 -->
        <form onsubmit='submitCommentForm(this,event)' enctype="multipart/form-data">
            <input type='hidden' name='post_id' value='<?php echo $postID; ?>'> <!-- 隱藏欄位，存放這則留言屬於哪一篇貼文（post_id），值由 PHP 變數 $postID 產生 -->
            <textarea name='comment_content' placeholder='新增留言' required></textarea>    <!-- 留言內容的輸入區，必填（required） -->
            <input type="file" name="comment_images[]" multiple accept="image/*" style="margin-top:5px;"> <!-- 上傳圖片的欄位，可以選多張圖片（multiple），只接受圖片檔案（accept="image/*"），上方間距5px（style="margin-top:5px;"） -->
            <button type='submit'>留言</button><!-- 送出按鈕，按下後送出表單 -->
        </form>
        <?php
        $response['success'] = true;// 回傳成功訊息（通常用在AJAX回應中，表示操作成功）
        $response['html'] = ob_get_clean();// 把之前用 ob_start() 開啟的輸出緩衝區內容清空並存進 response['html']，用來回傳動態 HTML 給前端
    }
    // 處理用戶更換頭像的 AJAX 請求，且使用者已登入
    if ($_POST['ajax_action'] === 'change_avatar' && isset($_SESSION["user"])) {
        $uid = intval($_SESSION["user"]["UID"]);// 取得目前登入使用者的 UID，轉成整數
         // 檢查有沒有上傳檔案，且檔案沒有錯誤
        if (isset($_FILES["avatar"]) && $_FILES["avatar"]["error"] == 0) {
            $targetDir = "uploads/";  // 設定上傳檔案要儲存的資料夾
            if (!is_dir($targetDir)) mkdir($targetDir, 0777, true); // 如果資料夾不存在就建立它，權限設為 0777（所有人可讀寫執行）
            $fileName = uniqid() . "_" . basename($_FILES["avatar"]["name"]); // 產生一個唯一的檔名，避免重複，格式為：uniqid_原本的檔名
            $targetFilePath = $targetDir . $fileName; // 設定完整的檔案路徑（資料夾＋檔名）
            $fileType = strtolower(pathinfo($targetFilePath, PATHINFO_EXTENSION));  // 取得檔案副檔名並轉成小寫
            $allowedTypes = ["jpg", "jpeg", "png", "gif"]; // 設定允許上傳的檔案類型（jpg, jpeg, png, gif）
            // 檢查副檔名是否在允許的類型裡
            if (in_array($fileType, $allowedTypes)) {
                // 將上傳的臨時檔案移到目標資料夾
                if (move_uploaded_file($_FILES["avatar"]["tmp_name"], $targetFilePath)) {
                    $conn->query("UPDATE User SET Avatar='$fileName' WHERE UID=$uid"); // 更新資料庫，將使用者的頭像欄位改為新檔名
                    $_SESSION["user"]["Avatar"] = $fileName;// 更新 session 裡的頭像資訊
                    $response['success'] = true;// 回傳成功訊息
                    $response['avatarUrl'] = $targetDir . $fileName;// 回傳新頭像的網址（給前端顯示用）
                }
            }
        }
    }
    // 檢查這是一個刪除帳號的 AJAX 請求，且使用者已登入
    if ($_POST['ajax_action'] === 'delete_account' && isset($_SESSION["user"])) {
        $uid = intval($_SESSION["user"]["UID"]);// 取得目前登入使用者的UID（轉成整數，避免SQL注入）
        $conn->query("DELETE FROM Comment WHERE UserUID = $uid");// 刪除這個使用者所有留言
        $conn->query("DELETE FROM `Like` WHERE UserUID = $uid"); // 刪除這個使用者所有按讚紀錄
        $conn->query("DELETE FROM Post WHERE AuthorUID = $uid");// 刪除這個使用者發表的所有貼文
        $conn->query("DELETE FROM User WHERE UID = $uid"); // 刪除這個使用者的帳號資料
        session_destroy();  // 登出（銷毀session，清除登入狀態）
        $response['success'] = true; // 回傳成功訊息
    }
    // 新增音樂（只有管理員）
    // 檢查這是一個新增音樂的 AJAX 請求，且目前登入的使用者是 Admin（管理員）
    if ($_POST['ajax_action'] === 'add_music' && isset($_SESSION["user"]) && $_SESSION["user"]["Role"] === "Admin") {
        $name = $conn->real_escape_string($_POST['music_name']);// 取得音樂名稱，並進行 SQL 字串跳脫（防止SQL注入）
        if (isset($_FILES["music_file"]) && $_FILES["music_file"]["error"] == 0) {  // 檢查是否有檔案上傳，且檔案沒出錯
            $targetDir = "music/"; // 設定音樂上傳的目錄
            if (!is_dir($targetDir)) mkdir($targetDir, 0777, true); // 如果資料夾不存在，就建立資料夾（權限設為0777，允許讀寫）
            $fileName = uniqid() . "_" . basename($_FILES["music_file"]["name"]);// 產生唯一檔名（避免檔名重複），格式：uniqid_原始檔名
            $targetFilePath = $targetDir . $fileName;// 組合完整的目標檔案路徑
            $fileType = strtolower(pathinfo($targetFilePath, PATHINFO_EXTENSION)); // 取得檔案副檔名，並轉成小寫
            $allowedTypes = ["mp3", "ogg", "wav"]; // 允許的音樂檔案格式
            if (in_array($fileType, $allowedTypes)) {// 檢查檔案格式是否允許
                if (move_uploaded_file($_FILES["music_file"]["tmp_name"], $targetFilePath)) {  // 將暫存檔案移動到目標資料夾
                    $insertQ = "INSERT INTO Music (Name, File) VALUES ('$name', '$fileName')"; // 寫入音樂資料到資料庫
                    if ($conn->query($insertQ)) {
                        $response['success'] = true; // 寫入成功，回傳成功訊息
                    } else {
                        $response['success'] = false; // 資料庫寫入失敗
                        $response['msg'] = '資料庫寫入失敗';
                    }
                } else {
                    $response['success'] = false;// 檔案移動失敗（可能是權限、目錄錯誤等）
                    $response['msg'] = '檔案移動失敗';
                }
            } else {
                $response['success'] = false;// 檔案格式不正確（不在允許清單內）
                $response['msg'] = '檔案格式錯誤';
            }
        } else {
            $response['success'] = false; // 沒有檔案上傳或檔案上傳出錯
            $response['msg'] = '檔案上傳失敗';
        }
    }
    // 檢查這是一個更改暱稱的 AJAX 請求，且使用者已登入
    if ($_POST['ajax_action'] === 'change_nickname' && isset($_SESSION["user"])) {
        $newNickname = $conn->real_escape_string($_POST['new_nickname']);   // 取得前端傳來的新暱稱，並用 real_escape_string  // $newNickname：字串，存放新暱稱
        $uid = intval($_SESSION["user"]["UID"]);// 取得目前登入使用者的 UID，並轉成整數（避免被惡意輸入影響）// $uid：整數，存放目前使用者的 UID
         // 檢查新暱稱不是空字串
        if ($newNickname !== '') {
            $conn->query("UPDATE User SET Nickname = '$newNickname' WHERE UID = $uid");  // 執行 SQL 指令，把資料庫該使用者的暱稱更新成新暱稱
            $_SESSION["user"]["Nickname"] = $newNickname;  // 同步更新 session 裡的暱稱，讓前端不用重新登入就能看到新暱稱
            $response['success'] = true;  // 設定回傳給前端的 JSON 回應，表示成功並帶回新暱稱
            $response['newNickname'] = $newNickname;//newNickname(新暱稱)
        }
    }

    echo json_encode($response);// 最後把 $response 這個陣列轉成 JSON 格式回傳給前端
    exit;// 結束 PHP 程式，不再繼續執行後面的程式碼
}
// 檢查表單送出方式是 POST，且有按下 register_step1 的按鈕
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["register_step1"])) {
    $email = $_POST["email"];// 取得使用者填寫的 email
    $password = $_POST["password"]; // 取得使用者填寫的密碼
    $confirmPassword = $_POST["confirm_password"]; // 取得使用者填寫的確認密碼
    $check = $conn->query("SELECT * FROM User WHERE Email = '$email'");// 到資料庫查詢這個 email 是否已經註冊過
     // 如果查詢結果有資料，代表這個 email 已經註冊過
    if ($check->num_rows > 0) {
        echo "<script>alert('帳號已存在！'); window.location.href='index.php?action=register_step1';</script>"; // 用 JavaScript 跳出 alert 提示帳號已存在，並導回註冊頁
        exit;// 結束程式，不再執行後續程式碼
    } elseif ($password !== $confirmPassword) {// 如果密碼與確認密碼不一致
        echo "<script>alert('密碼與確認密碼不一致！'); window.location.href='index.php?action=register_step1';</script>";// 用 JavaScript 跳出 alert 提示密碼不一致，並導回註冊頁
        exit; // 結束程式
    } else { // 如果 email 沒註冊過且密碼一致
        $_SESSION["register_email"] = $email;// 把 email 暫存到 session，給下一步用
        $_SESSION["register_password"] = password_hash($password, PASSWORD_DEFAULT);// 把密碼加密後暫存到 session，給下一步用
        header("Location: index.php?action=register_step2");// 重新導向到註冊第二步
        exit; // 結束程式
    }
}
// 檢查這是一個 POST 請求，且有按下「register_step2」的表單按鈕
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["register_step2"])) {
    $nickname = $_POST["nickname"];// 取得使用者輸入的暱稱
    $avatar = null; // 預設頭像檔名為 null（還沒上傳的狀態）
    // 檢查使用者有沒有上傳頭像，且檔案沒有錯誤
    if (isset($_FILES["avatar"]) && $_FILES["avatar"]["error"] == 0) {
        $targetDir = "uploads/";// 設定上傳檔案的資料夾為 "uploads/"
        if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);  // 如果資料夾不存在就建立一個（權限設為0777，允許所有人讀寫）
        $fileName = uniqid() . "_" . basename($_FILES["avatar"]["name"]);// 產生一個唯一的檔名，避免檔名重複，並保留原檔案名稱
        $targetFilePath = $targetDir . $fileName;// 組合出完整的檔案儲存路徑
        $fileType = strtolower(pathinfo($targetFilePath, PATHINFO_EXTENSION)); // 取得副檔名（小寫）
        $allowedTypes = ["jpg", "jpeg", "png", "gif"];// 設定允許的圖片格式
         // 檢查副檔名是否在允許的格式裡
        if (in_array($fileType, $allowedTypes)) {
              // 嘗試把上傳的檔案移動到指定的資料夾
            if (move_uploaded_file($_FILES["avatar"]["tmp_name"], $targetFilePath)) {
                $avatar = $fileName;// 上傳成功，把檔名記錄下來（之後可以存進資料庫）
            } else {
                echo "<script>alert('上傳頭像失敗！');</script>"; // 上傳失敗，跳出警告訊息
            }
        } else {
            echo "<script>alert('僅支援 JPG, JPEG, PNG, GIF 格式的圖片！');</script>";// 如果檔案格式不對，跳出警告訊息
        }
    }
    $email = $_SESSION["register_email"];// 取得註冊時暫存在 session 裡的 email 和 password
    $password = $_SESSION["register_password"];

    // 建立一條 SQL 指令，將新用戶資料寫進 User 資料表
    // 這裡會把暱稱、信箱、密碼、大頭貼、角色寫入，角色預設是 'User'
    $sql = "INSERT INTO User (Nickname, Email, Password, Avatar, Role) VALUES ('$nickname', '$email', '$password', '$avatar', 'User')";
    // 執行 SQL 指令，判斷是否新增成功
    if ($conn->query($sql)) {
        unset($_SESSION["register_email"]); // 新增成功後，清除 session 裡暫存的 email 和 password
        unset($_SESSION["register_password"]);

        echo "<script>alert('註冊成功！請登入。'); window.location.href = 'index.php';</script>"; // 彈出成功訊息，並導回首頁
    } else {
        echo "<script>alert('註冊失敗！請檢查資料。');</script>";// 新增失敗時，彈出錯誤訊息
    }
}
// 如果收到登入表單送出的 POST 請求，且有按下登入按鈕
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["login"])) {
    $email = $_POST["email"];  // 取得登入表單輸入的 email 和 password
    $password = $_POST["password"];

    $sql = "SELECT * FROM User WHERE Email = '$email'";// 查詢資料庫是否有這個 email 的用戶
    $result = $conn->query($sql);
    // 如果查得到這個 email
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc(); // 取得用戶資料
         // 用 password_verify 檢查密碼是否正確
        if (password_verify($password, $user["Password"])) {
            $_SESSION["user"] = $user;// 密碼正確，把用戶資料存進 session，表示登入成功
            echo "<script>alert('登入成功！'); window.location.href = 'index.php';</script>";  // 彈出登入成功訊息，並導回首頁
        } else {
            echo "<script>alert('密碼錯誤！');</script>";// 如果密碼錯誤時，彈出提示視窗
        }
    } else {
        echo "<script>alert('用戶不存在！'); window.location.href = 'index.php';</script>";// 如果查無此用戶，彈出提示並跳轉回首頁
        exit;
    }
}
// 處理修改密碼的請求
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["change_password"])) {
    $uid = $_SESSION["user"]["UID"]; // 取得目前登入者的 UID
    $old = $_POST["old_password"];// 取得表單輸入的舊密碼
    $new = $_POST["new_password"];//新密碼
    $confirm = $_POST["confirm_password"];//確認密碼
    $userRow = $conn->query("SELECT * FROM User WHERE UID=$uid")->fetch_assoc(); // 從資料庫撈出目前使用者的資料
     // 檢查舊密碼是否正確
    if (!password_verify($old, $userRow["Password"])) {
        echo "<script>alert('舊密碼錯誤！');</script>"; // 舊密碼錯誤，彈出提示
    } elseif ($new !== $confirm) { // 檢查新密碼與確認密碼是否一致
        echo "<script>alert('新密碼與確認密碼不一致！');</script>";  // 新密碼與確認密碼不一致，彈出提示
    } else {
        $hashed = password_hash($new, PASSWORD_DEFAULT); // 新密碼通過檢查，進行加密
        $conn->query("UPDATE User SET Password='$hashed' WHERE UID=$uid"); // 更新資料庫中的密碼
        echo "<script>alert('密碼修改成功！請重新登入！'); window.location.href='index.php?logout=true';</script>"; // 密碼修改成功，提示並跳轉到登出（需重新登入）
        exit;
    }
}
// 如果網址帶有 ?logout 參數，執行登出
if (isset($_GET["logout"])) {
    session_destroy();// 銷毀 session，登出使用者
    header("Location: index.php");// 重新導向到首頁 index.php
    exit; // 停止後續程式執行
}

$musicList = [];// 宣告一個空的音樂清單陣列
$musicQuery = $conn->query("SELECT * FROM Music ORDER BY MusicID ASC");// 查詢 Music 資料表，取得所有音樂資料，依照 MusicID 由小到大排序
while ($row = $musicQuery->fetch_assoc()) $musicList[] = $row;// 把查詢到的每一筆資料都放進 $musicList 陣列

$defaultMusicId = count($musicList) ? $musicList[0]['MusicID'] : 0;// 如果有音樂資料，預設第一首的 MusicID 為預設值，否則設為 0
$curMusicId = isset($_COOKIE['music_id']) && $_COOKIE['music_id'] ? intval($_COOKIE['music_id']) : $defaultMusicId;// 判斷 cookie 裡有沒有 music_id，有就用 cookie 的值，否則用預設值
$curMusicFile = '';// 宣告目前要播放的音樂檔案變數
foreach ($musicList as $m) if ($m['MusicID'] == $curMusicId) $curMusicFile = $m['File'];// 從音樂清單找出和 $curMusicId 相同的音樂，把它的檔名存到 $curMusicFile
if (!$curMusicFile && isset($musicList[0])) $curMusicFile = $musicList[0]['File'];// 如果找不到對應的音樂檔案，預設用第一首音樂的檔案

function parsePostContentWithImages($content) {// 定義一個函式，將貼文內容裡的 [IMG]標籤替換成圖片 HTML
    $content = nl2br(htmlspecialchars($content));// 先把內容做 HTML 轉義，並把換行符號轉成 <br>
    $img_index = 0;// 設定圖片索引（目前沒用到，但可用於多圖識別）
    $content = preg_replace_callback('/\[IMG\](.*?)\[\/IMG\]/', function($matches) use (&$img_index) {  // 用正則表達式找出 [IMG]xxx[/IMG] 片段，並用 callback 函式取代
        $imgPath = "uploads/" . htmlspecialchars($matches[1]);   // 取得圖片路徑，加上 uploads/ 目錄

        // 組出圖片的 HTML 標籤，帶有 class、style 等屬性
        // 回傳圖片 HTML，會自動取代原本的 [IMG]xxx[/IMG]
        //width:120px;：圖片寬度固定 120 像素
        //height:120px;：圖片高度固定 120 像素
        //object-fit:cover;：圖片裁切填滿框框，不變形
        //margin-top:5px;：圖片上方留 5px 空間
        //border-radius:5px;：圖片圓角 5px
        //cursor:pointer;：滑鼠移到圖片上會變成手指圖示
        //display:inline-block;：圖片橫向排列，不會換行
        //margin-right:5px;：圖片右邊留 5px 空間
        $img_html = "<img src='$imgPath' class='post-img-preview' style='width:120px;height:120px;object-fit:cover;margin-top:5px;border-radius:5px;cursor:pointer;display:inline-block;margin-right:5px;' alt='貼文圖片' onclick='showImageModal(\"$imgPath\")'>";
        $img_index++;// 每處理一張圖片，圖片索引加1
        if ($img_index % 4 == 1) $img_html = "<div class='post-img-row'>$img_html";// 如果這是每4張圖片的「第一張」，就加上<div class='post-img-row'>，開始一個新的圖片列
        if ($img_index % 4 == 0) $img_html .= "</div>";// 如果這是每4張圖片的「第4張」，就在這行結束時加上</div>，結束這一列的div
        return $img_html;// 回傳這張圖片的HTML（已經加上可能的開頭或結尾div）
    }, $content);
    // 如果圖片總數不是4的倍數（剩下的圖片還沒關閉div），而且至少有一張圖片，就在最後加上</div>，把最後一列關掉
    if ($img_index % 4 != 0 && $img_index > 0) $content .= "</div>";
    return $content;// 回傳處理好的內容（包含所有圖片與包好的div）
}
?>
<!DOCTYPE html><!-- 宣告這是一個 HTML5 文件 -->

<html lang="zh-Hant"><!-- 定義文件使用的語言為繁體中文 -->

<head>
    <meta charset="UTF-8"><!-- 設定網頁的字符編碼為 UTF-8，確保可以正確顯示中文等多國語言 -->

    <meta name="viewport" content="width=device-width, initial-scale=1.0"><!-- 設定響應式設計，讓網頁在不同裝置（如手機、平板、電腦）上都能良好顯示 -->

    <title>遊戲論壇</title><!-- 設定瀏覽器標籤上顯示的網頁標題 -->
    <style>
        :root {  /* 全站預設顏色變數，方便主題切換 */
            --bg-color: #f4f4f4;              /* 頁面背景顏色 */
            --container-bg: #fff;               /* 主要容器背景色 */
            --text-color: #000;                 /* 主要文字顏色 */
            --border-color: #ddd;               /* 邊框顏色 */
            --shadow-color: rgba(0,0,0,0.1);  /* 陰影顏色 */
            --post-bg: #f9f9f9;               /* 貼文區塊背景 */
            --profile-post-bg: #f5f8ff;       /* 個人頁貼文背景 */
            --button-bg: #007bff;             /* 按鈕背景色 */
            --button-hover-bg: #0056b3;       /* 按鈕滑過背景 */
            --delete-bg: #ff4d4d;             /* 刪除按鈕背景 */
            --delete-hover-bg: #ff1a1a;       /* 刪除按鈕滑過背景 */
            --modal-bg: rgba(0,0,0,0.2);      /* 彈窗背景遮罩 */
            --modal-box-bg: #fff;               /* 彈窗盒子背景 */
            --modal-box-shadow: 0 0 20px #3335; /* 彈窗盒子陰影 */
            --input-bg: #fff;                   /* 輸入框背景 */
            --input-text: #000;                 /* 輸入框文字 */
            --input-border: #ddd;               /* 輸入框邊框 */
        }
        body.dark-mode {/* 暗色模式下覆蓋顏色變數 */
            --bg-color: #1a1a1a;              /* 頁面背景顏色 */
            --container-bg: #23272f;          /* 主要容器背景色 */
            --text-color: #e9e9e9;            /* 主要文字顏色 */
            --border-color: #333;               /* 邊框顏色 */
            --shadow-color: rgba(0,0,0,0.5);  /* 陰影顏色 */
            --post-bg: #23272f;               /* 貼文區塊背景 */
            --profile-post-bg: #23272f;       /* 個人頁貼文背景 */
            --button-bg: #375a7f;             /* 按鈕背景色 */
            --button-hover-bg: #223a56;       /* 按鈕滑過背景 */
            --delete-bg: #d9534f;             /* 刪除按鈕背景 */
            --delete-hover-bg: #b52b27;       /* 刪除按鈕滑過背景 */
            --modal-bg: rgba(0,0,0,0.6);      /* 彈窗背景遮罩 */
            --modal-box-bg: #2d2d2d;          /* 彈窗盒子背景 */
            --modal-box-shadow: 0 0 20px #000b; /* 彈窗盒子陰影 */
            --input-bg: #23272f;              /* 輸入框背景 */
            --input-text: #e9e9e9;            /* 輸入框文字 */
            --input-border: #444;               /* 輸入框邊框 */
        }
        body {
            font-family: Arial, sans-serif;          /* 字型 */
            margin: 0;                               /* 外距0 */
            padding: 0;                              /* 內距0 */
            background-color: var(--bg-color);       /* 背景色用變數 */
            color: var(--text-color);                /* 文字顏色 */
            transition: background 0.3s, color 0.3s; /* 背景/文字顏色切換動畫 */
        }
        .container {/*框*/ 
            width: 80%;                               /* 寬度80% */
            max-width: 600px;                         /* 最大寬600px */
            margin: 50px auto;                        /* 上下50px，左右置中 */
            background: var(--container-bg);          /* 背景色 */
            padding: 20px;                            /* 內距20px */
            border-radius: 5px;                       /* 圓角5px */
            box-shadow: 0 0 10px var(--shadow-color); /* 陰影 */
            position: relative;                       /* 相對定位 */
            color: var(--text-color);                 /* 文字顏色 */
        }
        .form-group {margin-bottom: 15px;}  /* 表單區塊下方間距15px */
        input, textarea, button {/*輸入、文字區域、按鈕*/ 
            width: calc(100% - 20px);               /* 寬度100%-20px（內外距） */
            padding: 10px;                          /* 內距10px */
            margin: 10px;                           /* 外距10px */
            border: 1px solid var(--input-border);  /* 邊框1px，顏色用變數 */
            border-radius: 5px;                     /* 圓角5px */
            box-sizing: border-box;                 /* 包含邊框與內距計算寬度 */
            background: var(--input-bg);            /* 背景色 */
            color: var(--input-text);               /* 文字顏色 */
        }
        textarea {/*文字區域*/
            height: 60px; /* 高度60px */
            resize: none; /* 不能調整大小 */
        }
        button, .blue-button {/*按鈕，.藍色按鈕*/
            background-color: var(--button-bg); /* 背景色 */
            color: white;                       /* 文字白色 */
            cursor: pointer;                    /* 滑鼠指標為手 */
        }
        button:hover, .blue-button:hover {/*按鈕：懸停，.藍色按鈕：懸停 執行動作*/
            background-color: var(--button-hover-bg);/* 滑過時改變背景色 */
        }
        a {/*文字*/
            display: block;          /* 區塊顯示 */
            color: #007bff;          /* 文字藍色 */
            text-decoration: none;   /* 無底線 */
        }
        a:hover {   /*文字執行動作*/
			text-decoration: underline; /* 滑鼠滑過有底線 */
		}
        .link-bottom-left { /*左邊的按讚按鈕*/
			position: relative;     /* 相對定位 */
			left: 0;                /* 靠左 */
			bottom: 0;              /* 靠下 */
			margin: 10px 0 0 10px;  /* 外距：上10px、左10px */
			display: inline-block;  /* 行內區塊 */
		}
        .post, .comment {/*貼文,留言*/
			margin-bottom: 20px;                    /* 下方間距20px */
			padding: 10px;                          /* 內距10px */
			border: 1px solid var(--border-color);  /* 邊框1px */
			border-radius: 5px;                     /* 圓角5px */
			background: var(--post-bg);             /* 背景色 */
		}
        .post-title {/*貼文標題*/ 
            font-size: 18px;                        /* 字體18px */
            font-weight: bold;                      /* 粗體 */
            cursor: pointer;                        /* 指標為手 */
            user-select: none;                      /* 不能選取 */
            color: var(--text-color);               /* 文字顏色 */
            transition: color 0.2s;                 /* 顏色切換動畫 */
        }
        .post-title:hover {/*貼文標題執行動作*/ 
			color: #007bff;   /* 滑過變藍色 */
		}
        .post-header {/*貼文標頭 */
			display: flex;                   /* 彈性排列 */
			justify-content: space-between;  /* 左右對齊 */
			align-items: center;             /* 垂直置中 */
			margin-bottom: 10px;             /* 下方間距10px */
		}
        .post-header img {/*貼文標頭 圖片*/
			width: 50px;           /* 寬50px */
			height: 50px;          /* 高50px */
			border-radius: 50%;    /* 圓形 */
			margin-right: 10px;    /* 右側間距10px */
			cursor: pointer;       /* 指標為手 */
		}
        .delete-button, .profile-button {/*刪除按鈕 個人頁面的登出按鈕*/
            background-color: var(--delete-bg);  /* 背景色，使用 CSS 變數 --delete-bg（通常是紅色系，代表刪除） */
            color: white;                        /* 文字顏色為白色 */
            border: none;                        /* 無邊框 */
            cursor: pointer;                     /* 滑鼠移過去變成手指形狀，代表可以點擊 */
            width: 80px;                         /* 寬度 80 像素 */
            text-align: center;                  /* 文字置中 */
            border-radius: 5px;                  /* 圓角 5 像素 */
            font-size: 15px;                     /* 字體大小 15 像素 */
            margin: 3px 0;                       /* 上下外距 3 像素，左右為 0 */
            padding: 8px 0;                      /* 上下內距 8 像素，左右為 0 */
        }
        .delete-button:hover, .profile-button:hover {/*刪除按鈕 個人頁面的登出按鈕 額外功能*/
			background-color: var(--delete-hover-bg);  /* 滑過改背景色 */
		}
        .edit-button {/*編輯按鈕*/
			background-color: #28a745;  /* 背景綠色 */
			color: white;                 /* 白字 */
			border: none;                 /* 無邊框 */
			cursor: pointer;              /* 指標為手 */
			width: 80px;                  /* 寬80px */
			text-align: center;           /* 文字置中 */
			border-radius: 5px;           /* 圓角5px */
			font-size: 15px;              /* 字體15px */
			margin: 3px 0 3px 0;          /* 上下外距3px */
			padding: 8px 0;               /* 上下內距8px */
		}
        .edit-button:hover { /*編輯按鈕 額外功能*/
			background-color: #1e7e34; /* 滑過深綠 */
		}
        .like-button, .comment-toggle-btn {/*按讚按鈕 評論切換按鈕 */
            background-color: var(--button-bg); /* 背景色 */
            color: white;           /* 白字 */
            border: none;           /* 無邊框 */
            cursor: pointer;        /* 指標為手 */
            font-size: 14px;        /* 字體14px */
            border-radius: 5px;     /* 圓角5px */
            margin-right: 10px;     /* 右側間距10px */
            width: 40px;            /* 寬40px */
            height: 20px;           /* 高20px */
            padding: 0;             /* 無內距 */
            display: inline-block;  /* 行內區塊 */
            text-align: center;     /* 文字置中 */
            line-height: 20px;      /* 行高20px，使文字垂直置中 */
        }
        .like-button:hover, .comment-toggle-btn:hover {/*按讚按鈕 評論切換按鈕 額外功能*/
			background-color: var(--button-hover-bg);/* 滑過背景色 */
		}
        .like-count, .comment-count {/*點讚數留言數 */
			font-size: 14px;             /* 字體14px */
			color: var(--text-color);    /* 文字顏色 */
		}
        .comment-box {/*留言箱 */
			margin-top: 10px;   /* 上方間距10px */
		}
        .avatar-preview {/*頭像預覽*/
			margin: 10px 0;     /* 上下間距10px */
		}
        .avatar-preview img {/*頭像預覽 圖片*/
			max-width: 100px;     /* 最大寬100px */
			max-height: 100px;    /* 最大高100px */
			border-radius: 50%;   /* 圓形 */
		}
        .img-tip {/*圖片提示*/
			font-size: 13px;       /* 字體13px */
			color: #888;           /* 灰色 */
			margin: 2px 0 0 12px;  /* 上2px 左12px */
			text-align: left;      /* 文字靠左 */
		}
        .profile-header {/*個人資料標題*/
			display: flex;          /* 彈性排列 */
			align-items: center;    /* 垂直置中 */
			margin-bottom: 20px;    /* 下方間距20px */
		}
        .profile-header img {/*個人資料標題 圖片*/
			width: 80px;           /* 寬80px */
			height: 80px;          /* 高80px */
			border-radius: 50%;    /* 圓形 */
			margin-right: 20px;    /* 右側間距20px */
			cursor:pointer;        /* 指標為手 */
		}
        .profile-info {/*個人資料資訊*/
            font-size: 16px;   /* 字體16px */
        }
        .profile-posts {/*個人資料貼文*/
            margin-top: 20px; /* 上方間距20px */
        }
        .profile-posts .post {/*個人資料貼文.貼文*/
            background: var(--profile-post-bg);/* 個人頁貼文背景 */
        }
        .top-avatar {/*上方頭像 */
            position: absolute; /* 絕對定位 */
            top: 20px;          /* 距離頂部20px */
            right: 70px;        /* 距離右側70px */
            z-index: 10;        /* 堆疊層級10 */
        }
        .top-avatar img {/*上方頭像 圖片*/
            width: 50px;                    /* 寬50px */
            height: 50px;                   /* 高50px */
            border-radius: 50%;             /* 圓形 */
            border: 2px solid #007bff;    /* 藍色邊框2px */
            cursor: pointer;                /* 指標為手 */
        }
        .top-music {/*上方音樂 */
            position: absolute;  /* 絕對定位 */
            top: 20px;           /* 距頂20px */
            right: 20px;         /* 距右20px */
            z-index: 11;         /* 層級11 */
        }
        .music-select-panel {/*音樂選擇面板 */
            display:none;                           /* 預設隱藏 */
            position:absolute;                      /* 絕對定位 */
            top:45px;                               /* 距頂45px */
            right:0;                                /* 靠右 */
            background:var(--container-bg);         /* 背景色 */
            border:1px solid var(--border-color);   /* 邊框1px */
            border-radius:8px;                      /* 圓角8px */
            box-shadow:0 2px 10px #bbb;             /* 陰影 */
            padding:10px;                           /* 內距10px */
            z-index:100;                            /* 層級100 */
            min-width:220px;                        /* 最小寬220px */
            max-width:none;                         /* 無最大寬 */
            width:auto;                             /* 自動寬度 */
            text-align:center;                      /* 文字置中 */
        }
        .music-select-panel label {/*音樂選擇面板 標籤*/
            display: flex;                  /* 彈性排列 */
            align-items: center;            /* 垂直置中 */
            justify-content: space-between; /* 左右分散 */
            margin: 4px 0;                  /* 上下外距4px */
            width: 100%;                    /* 寬100% */
            white-space: normal;            /* 允許換行 */
            box-sizing: border-box;         /* 包含邊框與內距 */
        }
        .music-select-panel input[type="radio"] {/*音樂選擇面板 輸入[型態=“radio”]*/
            margin-right: 8px;   /* 右側間距8px */
            flex-shrink: 0;      /* 不縮小 */
        }
        .music-select-panel input[type="hidden"] {/*音樂選擇面板 輸入[型態=“radio”]*/
            display: none;  /* 隱藏 */
        }
        .music-select-panel strong {/*音樂選擇面板 強的*/
            display: block;      /* 區塊顯示 */
            text-align: center;  /* 文字置中 */
            margin-bottom: 8px;  /* 下方間距8px */
        }
        .profile-actions {/*個人資料行動 */
            margin-bottom: 20px;
            text-align: right;
        }
        .profile-actions form, .profile-actions a, .profile-actions button {/*個人資料操作表單 字 按鈕*/
            display: inline-block; /* 下方間距20px */
            margin-left: 10px;     /* 文字靠右 */
        }
        .modal-bg {/* MsgBox背景 */
            position: fixed;             /* 固定定位，讓背景覆蓋在畫面上方 */
            top:0;                       /* 從上方 0 開始 */
            left:0;                      /* 從左側 0 開始 */
            right:0;                     /* 右側到 0，撐滿整個寬度 */
            bottom:0;                    /* 下方到 0，撐滿整個高度 */
            background: var(--modal-bg); /* 使用 CSS 變數 --modal-bg 作為背景色（通常是半透明黑色，讓下方變暗） */
            z-index: 99;                 /* 疊層順序設為 99，確保蓋在大部分內容上面 */
        }
        .modal-box {/* MsgBox區塊 */
            position: fixed;                      /* 固定定位 */
            top:50%;                              /* 上螢幕中央 */
            left:50%;                             /* 左螢幕中央 */
            transform: translate(-50%,-50%);      /* 置中 */
            background: var(--modal-box-bg);      /* 背景色 */
            padding: 30px 20px;                   /* 內距 上下30px 左右20px */
            border-radius: 10px;                  /* 圓角10px */
            box-shadow: var(--modal-box-shadow);  /* 陰影 */ 
            z-index: 100;                         /* 層級100 */
            width: 300px;                         /* 寬300px */
            color: var(--text-color);             /* 文字顏色 */
        }
        .modal-box h3 {/* MsgBox區塊文字h3*/
            margin-top:0; /* 標題上方無間距 */
        }
        .modal-box .modal-btns {/* MsgBox區塊 按鈕*/
            display: flex;               /* 彈性排列 */
            justify-content: flex-start; /* 左對齊 */
            gap:5px;                     /* 間隔5px */
        }
        .modal-box .blue-button {/* MsgBox區塊 藍色按鈕*/
            width: 48%;  /* 寬48% */
        }
        .modal-box .cancel-btn {/* MsgBox區塊 取消按鈕*/
            background-color: #888; /* 灰色背景 */
            color: #fff;            /* 白字 */
        }
        .modal-box .cancel-btn:hover {/* MsgBox區塊 取消按鈕 額外功能*/
            background-color: #444; /* 滑過深灰 */
        }
        .comment-delete-btn {/*留言刪除按鈕 */
            width: 20px;                  /* 寬20px */
            height: 20px;                 /* 高20px */
            background: var(--delete-bg); /* 背景色 */
            color: #fff;                  /* 白字 */
            border: none;                 /* 無邊框 */
            border-radius: 3px;           /* 圓角3px */
            font-size: 16px;              /* 字體16px */
            font-weight: bold;            /* 粗體 */
            margin-left: 10px;            /* 左側間距10px */
            display: flex;                /* 彈性排列 */
            align-items: center;          /* 垂直置中 */
            justify-content: center;      /* 水平置中 */
            cursor: pointer;              /* 指標為手 */
            float:right;                  /* 靠右 */
        }
        .comment-delete-btn:hover {/*留言刪除按鈕 額外動作*/
            background: var(--delete-hover-bg);  /* 滑過背景色 */
        }
        .comment-inner {/*留言內容 */
            display: flex;               /* 彈性排列 */
            justify-content: flex-start; /* 靠左 */
            align-items: center;         /* 垂直置中 */
        }
        .comment-content-main {/*留言，留言內文 */
            flex: 1;          /* 佔滿剩餘空間 */
            text-align: left; /* 文字靠左 */
        }
        .comment-section {/*留言顯示 */
            display:none;  /* 預設隱藏 */
        }
        .post-content-pre {/*貼文 留言 前 */
            white-space: pre-wrap;   /* 保留換行與空白 */
        }
        .avatar-edit-label {/*頭像編輯標籤 */
            cursor:pointer;   /* 指標為手 */
        }
        .avatar-edit-preview {/*頭像編輯預覽 */
            display:block;           /* 區塊顯示 */
            margin:0 auto 10px auto; /* 置中，下方10px */
            max-width:120px;         /* 最大寬120px */
            max-height:120px;        /* 最大高120px */
            border-radius:50%;       /* 圓角化 */
        }
        .delete-account-btn {/*刪除帳戶按鈕 */
            background-color:var(--delete-bg);  /* 背景色 */
            color:#fff;                         /* 白字 */
            border:none;                        /* 無邊框 */
            cursor:pointer;                     /* 指標為手 */
            width:80px;                         /* 寬80px */
            text-align:center;                  /* 文字置中 */
            border-radius:5px;                  /* 圓角5px */
            font-size:15px;                     /* 字體15px */
            margin:3px 0;                       /* 上下外距3px */
            padding:8px 0;                      /* 上下內距8px */
        }
        .delete-account-btn:hover {/*刪除帳戶按鈕 額外功能*/
            background-color:var(--delete-hover-bg);   /* 滑過背景色 */
        }
        .music-add-btn {/*音樂新增按鈕 */
            background-color:var(--button-bg);  /* 背景色 */
            color:#fff;                         /* 白字 */
            border:none;                        /* 無邊框 */
            cursor:pointer;                     /* 指標為手 */
            width:80px;                         /* 寬80px */
            text-align:center;                  /* 文字置中 */
            border-radius:5px;                  /* 圓角5px */
            font-size:15px;                     /* 字體15px */
            margin:3px 0;                       /* 上下外距3px */
            padding:8px 0;                      /* 上下內距8px */
        }
        .music-add-btn:hover {/*音樂新增按鈕 額外功能*/
            background-color:var(--button-hover-bg);  /* 滑過背景色 */
        }
        .nickname-editable {/*暱稱編輯 */
            cursor:pointer;               /* 指標為手 */
            text-decoration: underline;   /* 底線 */
        }
        .top-theme {/*至頂主題 */
            position: absolute;    /* 絕對定位 */
            top: 20px;             /* 距頂20px */
            right: 70px;           /* 距右70px */
            z-index: 12;           /* 層級12 */
            display: flex;         /* 彈性排列 */
            gap: 10px;             /* 間距10px */
        }
        .theme-btn {/*主題按鈕 */
            background: var(--container-bg);/* 背景色 */
            border: 1px solid var(--border-color);/* 邊框1px */
            border-radius: 50%;/* 圓形 */
            width: 40px;/* 寬40px */
            height: 40px;/* 高40px */
            cursor: pointer;/* 指標為手 */
            font-size: 18px; /* 字體18px */
            color: var(--text-color);/* 文字顏色 */
            display: flex; /* 彈性排列 */
            align-items: center; /* 垂直置中 */
            justify-content: center;/* 水平置中 */
            transition: background 0.2s, color 0.2s; /* 動畫 */
        }
        .theme-btn.selected {/*主題按鈕 已選擇*/
            background: var(--button-bg);/* 背景色 */
            color: #fff;/* 白字 */
            border: 2px solid #007bff; /* 藍色邊框2px */
        }
        .theme-btn:hover {/*主題按鈕 額外動作*/
            background: var(--button-hover-bg); /* 滑過背景色 */
            color: #fff;/* 白字 */
        }
        .post-content-pre {/*貼文 留言 前 */
            display: none; /* 預設隱藏 */
            transition: max-height 0.3s, opacity 0.3s;/* 動畫 */
            max-height: 0;/* 最大高度0 */
            opacity: 0; /* 透明 */
            overflow: hidden; /* 超出隱藏 */
        }
        .post-content-pre.open {/*貼文 留言 前 打開*/
            display: block;/* 顯示 */
            max-height: 1000px;/* 最大高度1000px */
            opacity: 1;/* 不透明 */
            margin-top: 10px; /* 上方間距10px */
        }
        #goTopBtn {/*前往頂部按鈕 */
            display: none; /* 預設隱藏 */
            position: fixed; /* 固定定位 */
            bottom: 40px; /* 距底40px */
            right: 40px; /* 距右40px */
            z-index: 999;/* 層級999 */
            background: var(--button-bg); /* 背景色 */
            color: #fff;   /* 白字 */
            border: none;/* 無邊框 */
            border-radius: 50%;/* 圓形 */
            width: 50px; /* 寬50px */
            height: 50px;/* 高50px */
            font-size: 24px; /* 字體24px */
            cursor: pointer;/* 指標為手 */
            box-shadow: 0 4px 8px var(--shadow-color);/* 陰影 */
            transition: background 0.2s;   /* 動畫 */
        }
        #goTopBtn:hover {/*前往頂部按鈕 額外動作*/
            background: var(--button-hover-bg); /* 滑過背景色 */
        }
        #imageModal {/*影像模態 */
            display:none; /* 隱藏 */
            position:fixed;  /* 固定 */
            top:0;left:0;right:0;bottom:0;/* 覆蓋全螢幕 */
            background:rgba(0,0,0,0.7); /* 黑色半透明背景 */
            z-index:10001;/* 層級10001 */
            align-items:center;  /* 垂直置中 */
            justify-content:center;  /* 水平置中 */
        }
        #imageModal img {/*影像模態 圖片*/
            max-width:90vw;  /* 最大寬90%螢幕 */
            max-height:90vh;/* 最大高90%螢幕 */
            display:block; /* 區塊顯示 */
            margin:auto;/* 置中 */
            border-radius:10px; /* 圓角10px */
            background:#fff; /* 白色背景 */
        }
        #imageModal .close-btn {/*影像模態 關閉按鈕*/
            position:absolute; /* 絕對定位 */
            top:30px;right:30px;/* 距頂30px右30px */
            font-size:32px; /* 字體32px */
            color:#fff; /* 白字 */
            background:rgba(0,0,0,0.3);/* 半透明背景 */
            border:none; /* 無邊框 */
            border-radius:50%;/* 圓形 */
            width:48px;height:48px; /* 寬高48px */
            line-height:48px;  /* 行高48px */
            text-align:center; /* 文字置中 */
            cursor:pointer;/* 指標為手 */
            z-index:10002;/* 層級10002 */
        }
        .post-img-row {/*貼文圖片 排列 */
            display:flex;flex-wrap:wrap;/* 貼文圖片橫向排列、可換行 */
        }
        .comment-img-row {/*留言圖片 排列 */
            display:flex; /* 留言圖片橫向排列 */
        }
        @media ( max-width: 700px) {/*媒體最大寬度限制為 700 像素*/
            .container {/*容器 */
                width: 98%;/* 小螢幕下容器寬98% */
            }
        }
    </style>
    <script>
        function ajaxPost(data, callback, fileInput) {
             // 如果有傳入檔案輸入欄位（例如上傳圖片、音樂等）
            if(fileInput){
                var formData = new FormData();// 建立 FormData 物件（可同時傳送文字與檔案）
                for(var k in data) formData.append(k, data[k]);// 將 data 物件中的每個欄位都加進 formData
                // 如果有選到檔案才處理
                if(fileInput.files.length > 0) {
                    // 處理頭像上傳
                    if(fileInput.name=="avatar") {
                        formData.append("avatar", fileInput.files[0]);
                    // 處理多張圖片上傳（貼文或留言）
                    } else if(fileInput.name=="post_images[]" || fileInput.name=="comment_images[]") {
                        for(var i=0; i<fileInput.files.length; i++){
                            formData.append(fileInput.name, fileInput.files[i]);
                        }
                    // 處理音樂檔案上傳
                    } else if(fileInput.name=="music_file") {
                        formData.append("music_file", fileInput.files[0]);
                    }
                }
                var xhr = new XMLHttpRequest();// 建立 AJAX 請求
                xhr.open("POST", "index.php", true);// 設定為 POST 請求，目標為 index.php
                xhr.onreadystatechange = function() {
                    // 當請求完成且成功時
                    if (xhr.readyState === 4 && xhr.status === 200) {
                        callback(xhr.responseText ? JSON.parse(xhr.responseText) : {});// 回呼 callback，並將回傳的 JSON 字串轉換成物件後傳入
                    }
                };
                xhr.send(formData); // 送出含檔案的表單資料
                return;// 結束，不再往下執行
            }
            // 沒有檔案要上傳時，走這裡（純文字資料）
            var xhr = new XMLHttpRequest();// 建立 AJAX 請求
            xhr.open("POST", "index.php", true);// 設定為 POST 請求，目標為 index.php
            xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");// 設定資料格式為表單格式
            xhr.onreadystatechange = function() {
                // 當請求完成且成功時
                if (xhr.readyState === 4 && xhr.status === 200) {
                    callback(xhr.responseText ? JSON.parse(xhr.responseText) : {});// 回呼 callback，並將回傳的 JSON 字串轉換成物件後傳入
                }
            };
            var str = "";
            for (var k in data) str += encodeURIComponent(k) + "=" + encodeURIComponent(data[k]) + "&";// 把 data 物件轉成 URL 編碼字串（key1=value1&key2=value2...）
            xhr.send(str);// 送出資料
        }
        function previewAvatar(input) {
    // 取得預覽區塊的 DOM 元素（用來顯示頭像預覽）
    const preview = document.getElementById('avatar-preview');
    
    // 檢查 input 有選擇檔案且檔案存在
    if (input.files && input.files[0]) {
        const reader = new FileReader(); // 建立 FileReader 物件，用來讀取圖片檔案
        
        // 當讀取完成時觸發，e.target.result 會是圖片的 base64 資料
        reader.onload = function (e) {
            // 將預覽區塊內容設為一張圖片，src 設為讀取到的圖片資料
            preview.innerHTML = `<img src="${e.target.result}" alt="頭像預覽">`;
        };
        // 以 DataURL 方式讀取檔案（會觸發 onload 事件）
        reader.readAsDataURL(input.files[0]);
    } else {
        // 如果沒有選擇檔案，清空預覽區塊
        preview.innerHTML = '';
    }
}
        function previewAvatarModal(input) { 
            const preview = document.getElementById('avatar-edit-preview');// 取得預覽圖片的 <img> 標籤
            // 檢查是否有選擇檔案，且檔案存在
            if (input.files && input.files[0]) {
                const reader = new FileReader();// 建立 FileReader 物件，用來讀取檔案內容
                reader.onload = function (e) {
                    preview.src = e.target.result; // 當檔案讀取完成時，把圖片設為 <img> 的 src（顯示預覽）
                };
                reader.readAsDataURL(input.files[0]);// 以 DataURL 方式讀取檔案（適用於圖片預覽）
            }
        }
        function showChangePwd() {
            document.getElementById('changePwdModal').style.display='block';// 顯示修改密碼的彈窗（將對應的 modal 區塊設為可見）
        }
        function hideChangePwd() {
            document.getElementById('changePwdModal').style.display='none';// 隱藏修改密碼的彈窗（將 id 為 'changePwdModal' 的元素 display 設為 'none'）
        }
        function submitPostForm(form, event) {
    event.preventDefault(); // 阻止表單的預設送出行為（避免頁面重新整理）

    // 準備要送出的資料物件，包含貼文標題和內容
    var data = {ajax_action:"new_post", title:form.title.value, content:form.content.value};

    // 取得圖片上傳欄位（多圖）
    var fileInput = form.querySelector('input[name="post_images[]"]');

    // 用 ajaxPost 函式送出資料與圖片
    ajaxPost(data, function(resp) {
        if(resp.success) location.reload(); // 如果新增成功，重新整理頁面顯示新貼文
    }, fileInput); // 將圖片欄位傳給 ajaxPost 做檔案上傳
}
        function likePost(postId, btn, countSpan) {
            ajaxPost({ajax_action:"like_post", post_id:postId}, function(resp){// 發送 AJAX 請求，告訴後端這個使用者要對哪一篇貼文按讚或取消讚
                if(resp.success) { // 當收到後端回傳的結果後，檢查是否成功
                    var n = resp.likeCount;// 從回傳結果中取得最新的按讚數
                    countSpan.textContent = "讚: " + (n>=1000 ? (Math.round(n/100)/10)+'K' : n);// 從回傳結果中取得最新的按讚數
                }
            });
        }
        // 刪除貼文函式
function deletePost(postId, btn, event) {
    event.preventDefault(); // 防止表單預設送出行為（避免頁面重整）
    if(confirm('確定要刪除這篇貼文嗎？')) { // 跳出確認視窗，詢問用戶是否真的要刪除
        // 發送 AJAX 請求到後端，要求刪除指定貼文
        ajaxPost({ajax_action:"delete_post", post_id:postId}, function(resp){
            if(resp.success) location.reload(); // 如果後端回傳成功，重新整理頁面（讓貼文消失）
        });
    }
}
        // 編輯貼文
        // 顯示編輯貼文的彈窗，並將現有標題與內容填入表單
function showEditPostModal(postId, title, content) {
    // 設定隱藏欄位，記錄欲編輯的貼文 ID
    document.getElementById('editPostId').value = postId;
    // 將原本的標題填入編輯欄位
    document.getElementById('editPostTitle').value = title;
    // 將原本的內容填入編輯欄位
    document.getElementById('editPostContent').value = content;
    // 顯示編輯貼文的彈窗
    document.getElementById('editPostModal').style.display = 'block';
}
        function hideEditPostModal() {
    // 取得編輯貼文的彈窗元素（ID 為 editPostModal）
    // 並將其顯示方式設為 'none'，讓彈窗隱藏起來
    document.getElementById('editPostModal').style.display = 'none';
}
        function submitEditPostForm(event) {
    event.preventDefault(); // 阻止表單的預設提交行為（避免整頁重整）

    var postId = document.getElementById('editPostId').value;           // 取得要編輯的貼文 ID
    var newTitle = document.getElementById('editPostTitle').value;      // 取得新的貼文標題
    var newContent = document.getElementById('editPostContent').value;  // 取得新的貼文內容

    // 發送 AJAX 請求到後端，要求編輯貼文
    ajaxPost(
        {ajax_action:'edit_post', post_id:postId, new_title:newTitle, new_content:newContent},
        function(resp){
            if(resp.success) {
                location.reload(); // 如果成功，重新整理頁面顯示最新內容
            } else {
                alert('修改失敗！'); // 若失敗，跳出提示訊息
            }
        }
    );
}
        function submitCommentForm(form, event) {
    event.preventDefault(); // 阻止表單的預設送出行為（避免頁面重新整理）
    // 組合要送出的資料，包含 AJAX 動作、貼文ID、留言內容
    var data = {
        ajax_action: "new_comment",
        post_id: form.post_id.value,
        comment_content: form.comment_content.value
    };
    // 取得圖片檔案的 input 欄位
    var fileInput = form.querySelector('input[name="comment_images[]"]');
    // 發送 AJAX 請求（含圖片檔案）
    ajaxPost(data, function(resp) {
        if(resp.success) { // 如果留言成功
            // 找到對應的留言區塊
            var section = document.getElementById("comment-section-" + form.post_id.value);
            if(section) {
                // 重新取得最新留言內容並更新區塊
                ajaxPost({ajax_action:"get_comments", post_id:form.post_id.value}, function(resp2){
                    if(resp2.success) section.innerHTML = resp2.html;
                });
            }
        }
    }, fileInput);
}
        function deleteComment(commentId, btn, event) {
    event.preventDefault(); // 阻止表單的預設提交行為（避免頁面重整）

    // 彈出確認視窗，讓使用者再次確認是否要刪除留言
    if(confirm('確定要刪除此留言嗎？')) {
        // 發送 AJAX 請求給後端，請求刪除指定的留言
        ajaxPost({ajax_action:"delete_comment", comment_id:commentId}, function(resp){
            if(resp.success) {
                // 如果刪除成功，找到該留言所屬的貼文 ID
                var postId = btn.closest('.comment-section').id.replace('comment-section-','');
                var section = document.getElementById("comment-section-"+postId);
                if(section) {
                    // 重新向後端取得最新的留言區塊 HTML，並更新畫面
                    ajaxPost({ajax_action:"get_comments", post_id:postId}, function(resp2){
                        if(resp2.success) section.innerHTML = resp2.html;
                    });
                }
            }
        });
    }
}
        function toggleCommentSection(postId) {
    // 取得對應貼文的留言區塊（根據 postId 組成的 id）
    var section = document.getElementById("comment-section-" + postId);
    // 如果留言區目前是顯示狀態
    if(section.style.display === "block") {
        section.style.display = "none";  // 隱藏留言區
        section.innerHTML = "";          // 清空留言內容
    } else {
        // 如果留言區目前是隱藏狀態，則用 AJAX 取得留言內容
        ajaxPost({ajax_action:"get_comments", post_id:postId}, function(resp){
            if(resp.success) {
                section.innerHTML = resp.html; // 將取得的留言 HTML 填入區塊
                section.style.display = "block"; // 顯示留言區
            }
        });
    }
}
       function showAvatarEditModal() {
    // 取得 id 為 'avatarEditModal' 的元素（更換頭像的彈窗）
    // 將其顯示方式設為 'block'，讓彈窗顯示在畫面上
    document.getElementById('avatarEditModal').style.display = 'block';
}
        function hideAvatarEditModal() {
    // 隱藏更換頭像的彈窗（將彈窗區塊 display 設為 none）
    document.getElementById('avatarEditModal').style.display = 'none';
    // 重設頭像上傳表單（清空檔案選擇）
    document.getElementById('avatar-edit-form').reset();
    // 把預覽頭像的圖片 src 還原成舊的頭像（data-old 屬性儲存原本頭像路徑）
    document.getElementById('avatar-edit-preview').src = document.getElementById('avatar-edit-preview').getAttribute("data-old");
}
        function submitAvatarEditForm(event) {
    event.preventDefault(); // 阻止表單預設送出行為（避免頁面重新整理）

    var form = document.getElementById('avatar-edit-form'); // 取得更換頭像的表單元素
    var fileInput = form.avatar; // 取得頭像檔案輸入框

    if (!fileInput.files.length) { // 如果沒有選擇任何檔案
        alert("請選擇頭像！"); // 跳出提示
        return; // 結束函式
    }

    // 發送 AJAX 請求，傳送更換頭像的資料
    ajaxPost(
        {ajax_action:"change_avatar"}, // 傳送的資料（指定要執行 change_avatar 動作）
        function(resp){ // 回呼函式，處理伺服器回應
            if(resp.success) { // 如果回傳成功
                // 將所有頭像圖片（大頭貼、頁面右上角等）更新為新頭像
                var imgs = document.querySelectorAll('.profile-header img, .top-avatar img');
                for(var i=0;i<imgs.length;i++) 
                    imgs[i].src = resp.avatarUrl + "?t=" + new Date().getTime(); // 加上時間參數，避免快取

                hideAvatarEditModal(); // 關閉頭像編輯彈窗
            } else {
                alert("頭像上傳失敗！"); // 上傳失敗時顯示錯誤訊息
            }
        },
        fileInput // 檔案輸入框，讓 ajaxPost 能夠一併上傳檔案
    );
}
        function deleteAccount() {
    // 跳出確認視窗，詢問用戶是否真的要註銷帳號
    if(confirm('註銷帳號將刪除所有紀錄且無法復原，確定要註銷嗎？')) {
        // 發送 AJAX 請求到後端，請求刪除帳號
        ajaxPost({ajax_action:"delete_account"}, function(resp){
            // 如果後端回傳成功
            if(resp.success) {
                alert("帳號已註銷，將返回首頁。"); // 提示用戶帳號已註銷
                window.location.href = "index.php"; // 導回首頁
            } else {
                alert("註銷失敗！"); // 若失敗則提示
            }
        });
    }
}
        function toggleMusicPanel() {
    var panel = document.getElementById('music-panel'); // 取得音樂選單的 DOM 元素
    // 切換 music-panel 顯示/隱藏
    panel.style.display = (panel.style.display == "block" ? "none" : "block"); // 如果目前是顯示，就隱藏；否則就顯示
}
       function selectMusic(id) {
    var player = document.getElementById('bg-music'); // 取得背景音樂播放器 audio 元素
    var src = document.getElementById('music-src-' + id).value; // 取得選中音樂的檔案路徑
    player.src = src;      // 設定播放器的音樂來源
    player.load();         // 重新載入音樂
    player.play();         // 播放音樂
    document.getElementById('music-panel').style.display = 'none'; // 關閉音樂選擇面板
    localStorage.setItem('music_id', src.split('/').pop()); // 把目前選的音樂檔名記錄到 localStorage（用於下次自動播放）
}
        // 監聽整個文件的點擊事件
document.addEventListener('click', function(e){
    var panel = document.getElementById('music-panel'); // 取得音樂選擇面板元素
    var btn = document.getElementById('music-btn');     // 取得音樂按鈕元素
    // 如果音樂面板存在、目前是顯示狀態，且點擊的不是面板內部，也不是音樂按鈕本身
    if(panel && panel.style.display=="block" && !panel.contains(e.target) && e.target!==btn) 
        panel.style.display="none"; // 則把音樂面板收起來（隱藏）
});
        window.addEventListener("DOMContentLoaded", function(){ // 等網頁 HTML 結構載入後才執行
    var player = document.getElementById('bg-music'); // 抓取背景音樂播放器
    if(player) { // 如果有這個播放器
        player.volume = 0.3; // 把音量設成 0.3（30%）
        var musicId = localStorage.getItem('music_id'); // 從本地儲存抓上次選的音樂檔名
        if(musicId) { // 如果有記錄下來的音樂
            // 找所有隱藏的音樂檔路徑
            for(var i=0; i<document.querySelectorAll('.music-select-panel input[type=hidden]').length; i++){
                var src = document.querySelectorAll('.music-select-panel input[type=hidden]')[i].value; // 取出每個音樂檔案路徑
                if(src.indexOf(musicId) !== -1) { // 如果這個路徑裡有目前記錄的檔名
                    player.src = src; // 設定播放器的音樂來源
                    break; // 找到了就跳出迴圈
                }
            }
        }
                var musicTime = localStorage.getItem('music_time'); // 從 localStorage 取出上次播放到的秒數
if(musicTime) {
    player.currentTime = parseFloat(musicTime);     // 如果有紀錄，設定播放器從那個時間點開始播
}
player.play();                                      // 自動播放音樂
setInterval(function(){
    if(!player.paused && !player.ended) {           // 如果音樂正在播放
        localStorage.setItem('music_time', player.currentTime); // 每秒儲存目前播放的秒數
        var srcParts = player.src.split('/');                 // 取得音樂檔案路徑
        var fileName = srcParts[srcParts.length-1];           // 取得音樂檔案名稱
        localStorage.setItem('music_id', fileName);           // 儲存目前播放的音樂檔名
    }
}, 1000); // 每 1 秒執行一次
            }
        });
        function stopMusicOnLogout() {
    var player = document.getElementById('bg-music'); // 取得背景音樂播放器
    if(player) {
        player.pause();           // 暫停音樂播放
        player.currentTime = 0;   // 將播放進度歸零
    }
    localStorage.removeItem('music_time'); // 清除儲存的音樂播放時間
    localStorage.removeItem('music_id');   // 清除儲存的音樂檔案ID
}
        function showNicknameEditModal() {
    // 顯示「修改暱稱」的彈窗
    document.getElementById('nicknameEditModal').style.display = 'block';
    // 把目前的暱稱填進輸入框，方便直接編輯
    document.getElementById('nickname-input').value = document.getElementById('nickname-display').textContent;
}
        function hideNicknameEditModal() {
    // 隱藏「修改暱稱」的彈窗
    document.getElementById('nicknameEditModal').style.display = 'none';
}
        function submitNicknameEditForm(event) {
    event.preventDefault(); // 阻止表單預設送出（不重新整理頁面）

    var newNickname = document.getElementById('nickname-input').value.trim(); // 取得輸入的新暱稱，去除前後空白

    if(newNickname === '') { // 如果沒有輸入內容
        alert('暱稱不可為空！'); // 跳出提示
        return; // 不繼續往下執行
    }

    // 用 AJAX 發送請求到後端，請求更改暱稱
    ajaxPost(
        {ajax_action:'change_nickname', new_nickname:newNickname}, // 傳送的資料
        function(resp){ // 回應後執行這個函式
            if(resp.success) { // 如果後端回傳成功
                document.getElementById('nickname-display').textContent = resp.newNickname; // 更新畫面上的暱稱
                hideNicknameEditModal(); // 關閉修改暱稱的彈窗
            } else {
                alert('修改暱稱失敗！'); // 如果失敗，跳出提示
            }
        }
    );
}
        function setTheme(theme) {
    // 如果主題是 dark（深色）
    if(theme === 'dark') {
        document.body.classList.add('dark-mode');     // 加上 dark-mode 樣式（切換成深色模式）
        localStorage.setItem('theme', 'dark');        // 把主題設定寫進瀏覽器 localStorage，讓下次進來還是深色
    } else {
        document.body.classList.remove('dark-mode');  // 拿掉 dark-mode 樣式（恢復成亮色模式）
        localStorage.setItem('theme', 'light');       // 把主題設定寫進 localStorage，記住用戶選擇
    }
    updateThemeButtons(); // 更新主題按鈕的外觀（讓選中的有高亮）
}
        function updateThemeButtons() {
    // 檢查目前 body 有沒有 dark-mode 這個 class（判斷是不是深色模式）
    var isDark = document.body.classList.contains('dark-mode');
    // 如果是深色模式，讓深色按鈕加上 selected 樣式，淺色按鈕移除 selected 樣式
    document.getElementById('theme-dark-btn').classList.toggle('selected', isDark);
    document.getElementById('theme-light-btn').classList.toggle('selected', !isDark);
}
        window.addEventListener("DOMContentLoaded", function(){// 當整個網頁內容（HTML 結構）載入完成時，執行裡面的程式碼
            var theme = localStorage.getItem('theme'); // 從本地儲存拿出主題設定（light 或 dark）
var hasUser = <?php echo isset($_SESSION['user']) ? 'true' : 'false'; ?>; // 判斷有沒有登入（有登入為 true）

if(hasUser) { // 如果有登入
    if(theme === 'dark') 
        document.body.classList.add('dark-mode');    // 主題設為深色模式
    else 
        document.body.classList.remove('dark-mode'); // 否則用亮色（預設）
} else { // 沒登入
    document.body.classList.remove('dark-mode');     // 一律用亮色模式
    localStorage.setItem('theme','light');           // 並存回亮色設定
}

updateThemeButtons(); // 更新右上角的主題按鈕狀態（顯示目前是亮色還是暗色）
        });
        function togglePostContent(postId) {
    // 取得指定貼文內容區塊的元素
    var content = document.getElementById('post-content-' + postId);
    if(content) {
        // 切換 open 樣式，有 open 就移除，沒有就加上，讓內容顯示或隱藏
        content.classList.toggle('open');
    }
}
        window.onscroll = function() {scrollFunction()}; // 當使用者滾動網頁時，自動執行 scrollFunction()
        function scrollFunction() {
    var btn = document.getElementById("goTopBtn"); // 取得「回到頂部」按鈕
    if (!btn) return; // 如果找不到按鈕就結束

    // 如果頁面往下捲超過 100px，就顯示按鈕，否則隱藏
    if (document.body.scrollTop > 100 || document.documentElement.scrollTop > 100) {
        btn.style.display = "block"; // 顯示按鈕
    } else {
        btn.style.display = "none"; // 隱藏按鈕
    }
}
        function goTop() {
    // 捲動畫面回到最上方，並且有平滑的動畫效果
    window.scrollTo({top:0, behavior:'smooth'});
}
        function showImageModal(src) {
    var modal = document.getElementById('imageModal');   // 取得顯示大圖的彈窗區塊
    var img = document.getElementById('imageModalImg');  // 取得彈窗裡面要顯示的圖片標籤
    img.src = src;                                       // 把圖片標籤的來源設成你點的那張圖片
    modal.style.display = 'flex';                        // 顯示彈窗（讓它浮現在畫面上）
}
        function hideImageModal() {
    // 隱藏圖片預覽的彈窗
    document.getElementById('imageModal').style.display = 'none';
    // 清空彈窗裡的圖片路徑（讓下次打開時不會顯示舊圖）
    document.getElementById('imageModalImg').src = '';
}
        // 新增音樂彈窗
        function showMusicAddModal() {
            document.getElementById('musicAddModal').style.display='block';// 顯示「新增音樂」的彈窗
        }
        function hideMusicAddModal() {
    // 隱藏新增音樂的彈窗
    document.getElementById('musicAddModal').style.display='none';
    // 重設表單內容（清空輸入欄位）
    document.getElementById('music-add-form').reset();
}
        function submitMusicAddForm(event) {
    event.preventDefault(); // 阻止表單預設送出（不重新整理頁面）
    var form = document.getElementById('music-add-form'); // 取得新增音樂的表單
    var fileInput = form.music_file; // 取得音樂檔案的輸入欄位
    var nameInput = form.music_name; // 取得音樂名稱的輸入欄位
    // 如果沒選音樂檔案或沒輸入名稱，顯示警告並停止
    if (!fileInput.files.length || !nameInput.value.trim()) {
        alert("請選擇音樂檔案並輸入名稱！");
        return;
    }
    // 用 ajaxPost 上傳音樂資料到後端
    ajaxPost(
        {ajax_action:"add_music", music_name:nameInput.value}, // 傳送的資料
        function(resp){ // 回應後的處理
            if(resp.success) {
                alert("音樂新增成功，請重新整理頁面！"); // 成功提示
                hideMusicAddModal(); // 關閉新增音樂彈窗
            } else {
                alert("音樂上傳失敗！" + (resp.msg ? " " + resp.msg : "")); // 失敗提示
            }
        },
        fileInput // 傳送音樂檔案
    );
}
    </script>
</head>
<body>
<div id="imageModal" onclick="hideImageModal()">
    <button class="close-btn" onclick="hideImageModal();event.stopPropagation();">&times;</button><!-- 關閉按鈕，點擊後關閉圖片彈窗，不會觸發外層的關閉事件 -->
    <img id="imageModalImg" src="" alt="預覽圖片" onclick="event.stopPropagation();" /> <!-- 顯示大圖的區塊，點擊圖片本身不會關閉彈窗 -->
</div>
<?php
$isShowMusic = false; // 預設不顯示音樂播放器

// 如果：1. 使用者已登入，且網址沒有 action、沒有 profile_uid 參數（在論壇主頁）
//   或 2. 有 profile_uid 參數（正在瀏覽個人頁）
if (
    (isset($_SESSION["user"]) && !isset($_GET["action"]) && !isset($_GET["profile_uid"])) ||
    (isset($_GET["profile_uid"]))
) {
    // 如果是在個人頁（網址有 profile_uid）
    if (isset($_GET["profile_uid"])) {
        $profile_uid = intval($_GET["profile_uid"]); // 取得個人頁的 UID
        // 如果登入者就是這個個人頁本人
        if (isset($_SESSION["user"]) && $_SESSION["user"]["UID"] == $profile_uid) {
            $isShowMusic = false; // 本人看自己個人頁，不顯示音樂播放器
        } else {
            $isShowMusic = true;  // 其他人看個人頁，要顯示音樂播放器
        }
    } else {
        $isShowMusic = true; // 在論壇主頁，顯示音樂播放器
    }
}
?>
<?php if($isShowMusic): ?><!-- 判斷是否要顯示音樂選擇和播放器區塊，如果 $isShowMusic 為 true 就顯示。 -->
    <div class="top-theme">
    <!-- 切換到亮色（正常）模式的按鈕，顯示太陽圖示 -->
    <button id="theme-light-btn" class="theme-btn" onclick="setTheme('light')" title="正常模式">🌞</button>
    <!-- 切換到暗色（深色）模式的按鈕，顯示月亮圖示 -->
    <button id="theme-dark-btn" class="theme-btn" onclick="setTheme('dark')" title="深色模式">🌙</button>
</div>
    <!-- 右上角音樂選擇區塊 -->
<div class="top-music" style="right: 20px;">
    <!-- 音樂按鈕，點擊後會打開/關閉音樂選單 -->
    <button id="music-btn" onclick="toggleMusicPanel()" 
        style="background:#fff;border-radius:50%;border:1px solid #aaa;width:40px;height:40px;cursor:pointer;font-size:18px;" 
        title="選擇音樂">🎵</button>
    <!-- 音樂選單（預設隱藏，按鈕點擊才會顯示） -->
    <div id="music-panel" class="music-select-panel" 
        style="right:0;left:auto;min-width:220px;max-width:none;width:auto;">
        <strong>選擇音樂</strong>
        <?php foreach($musicList as $m): ?>
            <label>
                <!-- 單選按鈕，選到的會打勾，點擊時切換音樂 -->
                <span style="flex:0 0 auto;">
                    <input type="radio" name="music_sel" 
                        <?php if($curMusicId==$m['MusicID'])echo'checked';?> 
                        onclick="selectMusic(<?php echo $m['MusicID'];?>)">
                </span>
                <!-- 顯示音樂名稱，太長時會省略 -->
                <span style="flex:1 1 auto;text-align:right;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                    <?php echo htmlspecialchars($m['Name']); ?>
                </span>
                <!-- 隱藏欄位，存放這首歌的檔案路徑 -->
                <input type="hidden" id="music-src-<?php echo $m['MusicID'];?>" 
                    value="music/<?php echo htmlspecialchars($m['File']); ?>">
            </label>
        <?php endforeach; ?>
        <!-- 如果沒有任何音樂，顯示提示訊息 -->
        <?php if(empty($musicList)) echo "<div>尚無音樂</div>"; ?>
    </div>
</div>
<!-- 隱藏的背景音樂播放器，自動播放並重複循環 -->
    <audio id="bg-music" src="<?php echo $curMusicFile ? "music/".htmlspecialchars($curMusicFile) : ""; ?>" autoplay loop style="display:none"></audio>
<?php endif; ?>
    <div class="container">
        <?php
// 如果使用者已登入，且目前不是在個人頁面
if (isset($_SESSION["user"]) && !isset($_GET["profile_uid"])) {
    // 取得使用者頭像路徑，如果沒有就用預設圖
    $avatarPath = $_SESSION["user"]["Avatar"] ? "uploads/" . $_SESSION["user"]["Avatar"] : "default-avatar.png";
    // 顯示右上角的頭像，點擊可以進入自己的個人頁
    echo "<div class='top-avatar'><a href='index.php?profile_uid=" . $_SESSION["user"]["UID"] . "'><img src='" . htmlspecialchars($avatarPath) . "' alt='我的頭像' title='個人資料'></a></div>";
}

// 如果網址有帶 profile_uid 參數（代表要看某個人的個人頁）
if (isset($_GET["profile_uid"])): 
    $profile_uid = intval($_GET["profile_uid"]);  // 取得個人頁的 UID，轉成整數
    $userQuery = "SELECT * FROM User WHERE UID = $profile_uid"; // 查詢這個用戶的資料
    $userResult = $conn->query($userQuery); // 執行查詢
    if ($userResult->num_rows > 0): // 如果有找到這個用戶
        $userData = $userResult->fetch_assoc(); // 取得用戶資料
        // 取得頭像路徑，沒有就用預設圖
        $avatarPath = $userData["Avatar"] ? "uploads/" . $userData["Avatar"] : "default-avatar.png";
        // 查詢這個用戶發表過的所有貼文，依照時間由新到舊
        $userPosts = $conn->query("SELECT * FROM Post WHERE AuthorUID = $profile_uid ORDER BY CreatedAt DESC");
?>
            <a href="index.php" style="margin-bottom:15px;">← 回論壇首頁</a><!-- 返回論壇首頁的連結 -->
            <div class="profile-actions">
    <!-- 如果目前登入的使用者就是這個個人頁的本人，才顯示下面的操作按鈕 -->
    <?php if (isset($_SESSION["user"]) && $_SESSION["user"]["UID"] == $profile_uid): ?>
        <!-- 登出按鈕 -->
        <form method="get" action="index.php" style="display:inline;" onsubmit="stopMusicOnLogout()">
            <button type="submit" name="logout" value="true" class="profile-button blue-button" style="background-color:#007bff;">登出</button>
        </form>
        <!-- 修改密碼按鈕 -->
        <button type="button" class="profile-button blue-button" style="background-color:#007bff;" onclick="showChangePwd()">修改密碼</button>
        <!-- 註銷帳號按鈕 -->
        <button type="button" class="delete-account-btn" onclick="deleteAccount()">註銷帳號</button>
    <?php endif; ?>
    <!-- 如果是本人而且身分是管理員，才顯示新增音樂按鈕 -->
    <?php if (isset($_SESSION["user"]) && $_SESSION["user"]["UID"] == $profile_uid && $_SESSION["user"]["Role"] === "Admin"): ?>
        <!-- 新增音樂按鈕 -->
        <button type="button" class="music-add-btn" onclick="showMusicAddModal()">新增音樂</button>
    <?php endif; ?>
</div>
            <!-- 新增音樂彈窗 -->
            <?php if (isset($_SESSION["user"]) && $_SESSION["user"]["UID"] == $profile_uid && $_SESSION["user"]["Role"] === "Admin"): ?>
            <!-- 新增音樂的彈窗（預設隱藏） -->
<div id="musicAddModal" class="modal-bg" style="display:none;" onclick="hideMusicAddModal()">
    <!-- 彈窗內容區，點擊這裡不會關閉彈窗 -->
    <div class="modal-box" onclick="event.stopPropagation()">
        <h3>新增音樂</h3>
        <!-- 新增音樂的表單，送出時會呼叫 submitMusicAddForm -->
        <form id="music-add-form" enctype="multipart/form-data" onsubmit="submitMusicAddForm(event)">
            <!-- 輸入音樂名稱 -->
            <input type="text" name="music_name" placeholder="音樂名稱" required>
            <!-- 上傳音樂檔案，只能選音訊檔 -->
            <input type="file" name="music_file" accept="audio/*" required>
            <!-- 格式提示文字 -->
            <div class="img-tip">僅支援 MP3, OGG, WAV 格式的音樂！</div>
            <!-- 送出與取消按鈕 -->
            <div class="modal-btns" style="margin-top:10px;">
                <button type="submit" class="blue-button">送出</button>
                <button type="button" class="cancel-btn" onclick="hideMusicAddModal()">取消</button>
            </div>
        </form>
    </div>
</div>
            <?php endif; ?>
            <!-- 更換頭像彈窗 -->
            <?php if (isset($_SESSION["user"]) && $_SESSION["user"]["UID"] == $profile_uid): ?>
            <!-- 更換頭像的彈窗（平常隱藏，點按鈕才會顯示） -->
<div id="avatarEditModal" class="modal-bg" style="display:none;" onclick="hideAvatarEditModal()">
    <!-- 彈窗內容區，點擊內容區不會關閉彈窗 -->
    <div class="modal-box" onclick="event.stopPropagation()">
        <h3>更換頭像</h3>
        <!-- 上傳頭像的表單，送出時會用 JavaScript 處理，不會直接刷新頁面 -->
        <form id="avatar-edit-form" enctype="multipart/form-data" onsubmit="submitAvatarEditForm(event)">
            <!-- 頭像預覽圖片，顯示目前的頭像，選新圖時會即時更新 -->
            <img id="avatar-edit-preview" class="avatar-edit-preview" data-old="<?php echo htmlspecialchars($avatarPath); ?>" src="<?php echo htmlspecialchars($avatarPath); ?>" alt="頭像預覽">
            <!-- 選擇新頭像的檔案上傳欄位，只能選圖片 -->
            <input type="file" name="avatar" accept="image/*" onchange="previewAvatarModal(this)">
            <!-- 小提示：只接受這些圖片格式 -->
            <div class="img-tip">僅支援 JPG, JPEG, PNG, GIF 格式的圖片！</div>
            <!-- 下方按鈕區 -->
            <div class="modal-btns" style="margin-top:10px;">
                <button type="submit" class="blue-button">送出</button> <!-- 送出按鈕 -->
                <button type="button" class="cancel-btn" onclick="hideAvatarEditModal()">取消</button> <!-- 取消按鈕，關閉彈窗 -->
            </div>
        </form>
    </div>
</div>
            <?php endif; ?>
            <!-- 修改密碼的彈窗（預設隱藏） -->
<div id="changePwdModal" class="modal-bg" style="display:none;" onclick="hideChangePwd()">
    <!-- 彈窗內容區塊，點擊內容本身不會關閉彈窗 -->
    <div class="modal-box" onclick="event.stopPropagation()">
        <h3>修改密碼</h3>
        <!-- 修改密碼表單 -->
        <form method="POST">
            <!-- 輸入舊密碼 -->
            <input type="password" name="old_password" placeholder="舊密碼" required>
            <!-- 輸入新密碼 -->
            <input type="password" name="new_password" placeholder="新密碼" required>
            <!-- 再次確認新密碼 -->
            <input type="password" name="confirm_password" placeholder="確認新密碼" required>
            <div class="modal-btns">
                <!-- 送出按鈕 -->
                <button type="submit" name="change_password" class="blue-button">送出</button>
                <!-- 取消按鈕，點擊會關閉彈窗 -->
                <button type="button" class="cancel-btn" onclick="hideChangePwd()">取消</button>
            </div>
        </form>
    </div>
</div>
            <!-- 修改暱稱彈窗 -->
            <?php if (isset($_SESSION["user"]) && $_SESSION["user"]["UID"] == $profile_uid): ?>
            <!-- 修改暱稱的彈窗（模態視窗）開始 -->
<div id="nicknameEditModal" class="modal-bg" style="display:none;" onclick="hideNicknameEditModal()">
    <!-- 彈窗內容區塊，點擊內容本身不會關閉視窗 -->
    <div class="modal-box" onclick="event.stopPropagation()">
        <h3>修改暱稱</h3>
        <!-- 修改暱稱表單，送出時會執行 submitNicknameEditForm(event) -->
        <form id="nickname-edit-form" onsubmit="submitNicknameEditForm(event)">
            <!-- 輸入新的暱稱，必填 -->
            <input type="text" id="nickname-input" name="new_nickname" required>
            <div class="modal-btns" style="margin-top:10px;">
                <!-- 送出按鈕 -->
                <button type="submit" class="blue-button">送出</button>
                <!-- 取消按鈕，點擊會關閉彈窗 -->
                <button type="button" class="cancel-btn" onclick="hideNicknameEditModal()">取消</button>
            </div>
        </form>
    </div>
</div>
<!-- 修改暱稱的彈窗結束 -->
            <?php endif; ?>
            <div class="profile-header">
                <!-- 用戶頭像，如果是本人可以點擊更換頭像 -->
                <img src="<?php echo htmlspecialchars($avatarPath); ?>" alt="頭像"
                <?php if (isset($_SESSION["user"]) && $_SESSION["user"]["UID"] == $profile_uid): ?>
                    onclick="showAvatarEditModal()" title="點擊更換頭像"
                <?php endif; ?>
                >
                <div class="profile-info">
                    <!-- 顯示暱稱，自己看自己的頁面時可以點擊修改 如果是自己看自己的個人頁，才允許點擊修改暱稱-->
                    <div><strong>暱稱：</strong><span id="nickname-display" class="nickname-editable" <?php if (isset($_SESSION["user"]) && $_SESSION["user"]["UID"] == $profile_uid) echo 'onclick="showNicknameEditModal()" title="點擊修改暱稱"'; ?>><?php echo htmlspecialchars($userData["Nickname"]); ?></span></div>
                    <div><strong>身份：</strong><?php echo htmlspecialchars($userData["Role"]); ?></div> <!-- 顯示用戶身份（例如 User、Admin） -->
                    <div><strong>電子郵件：</strong><?php echo htmlspecialchars($userData["Email"]); ?></div><!-- 顯示用戶電子郵件 -->
                </div>
            </div>
            <div class="profile-posts"><!-- 用戶發表過的貼文區塊 -->
                <h3>發表過的貼文</h3>
                <?php
                if ($userPosts->num_rows > 0) { // 如果這個使用者有發表過貼文（資料筆數大於 0）
    while ($post = $userPosts->fetch_assoc()) { // 逐一抓出每一篇貼文
        echo "<div class='post'>";
        // 貼文標題，點擊可以展開/收合內容
        echo "<div class='post-title' onclick='togglePostContent(".$post["PostID"].")'>" . htmlspecialchars($post["Title"]) . "</div>";
        // 顯示貼文的發佈時間
        echo "<div><small>發布時間: " . $post["CreatedAt"] . "</small></div>";
        // 貼文內容（含圖片），預設收合
        echo "<p class='post-content-pre' id='post-content-".$post["PostID"]."'>" . parsePostContentWithImages($post["Content"]) . "</p>";
        echo "</div>";
    }
} else {
    // 如果沒有任何貼文
    echo "<div>尚未發表任何貼文。</div>";
}
                ?>
            </div>
        <?php
            else:
                // 如果找不到這個用戶
                echo "<div>找不到該用戶資料。</div>";
            endif;
        else:
        ?>
       <?php if (!isset($_SESSION["user"])): ?>   <!-- 如果使用者還沒登入 -->
    <?php if (!isset($_GET["action"])): ?> <!-- 並且網址沒有帶 action 參數（表示目前在登入頁） -->
                <h1>登入</h1>
                <form method="POST"> <!-- 建立一個表單，送出方式是 POST -->
    <div class="form-group">
        <label for="email">電子郵件</label>
        <input type="email" name="email" required> <!-- 輸入電子郵件（必填） -->
    </div>
    <div class="form-group">
        <label for="password">密碼</label>
        <input type="password" name="password" required> <!-- 輸入密碼（必填，隱藏顯示） -->
    </div>
    <button type="submit" name="login">登入</button> <!-- 送出表單，按下會登入 -->
</form>
                <a href="?action=register_step1">沒有帳號？註冊</a> <!-- 註冊連結，點擊後跳到註冊頁面 -->
            <?php elseif ($_GET["action"] == "register_step1"): ?><!-- 如果網址帶有 ?action=register_step1，就顯示註冊第一步的畫面 -->
                <h1>註冊 - 第一步</h1>
                <form method="POST"> <!-- 使用 POST 方法送出表單資料 -->
    <div class="form-group">
        <label for="email">電子郵件</label>
        <input type="email" name="email" required> <!-- 輸入電子郵件，必填 -->
    </div>
    <div class="form-group">
        <label for="password">密碼</label>
        <input type="password" name="password" required> <!-- 輸入密碼，必填 -->
    </div>
    <div class="form-group">
        <label for="confirm_password">確認密碼</label>
        <input type="password" name="confirm_password" required> <!-- 再輸入一次密碼，必填 -->
    </div>
    <button type="submit" name="register_step1">下一步</button> <!-- 送出表單，進入註冊下一步 -->
</form>
                <div class="link-bottom-left">
    <!-- 提供一個連結，讓已有帳號的使用者可以回到登入頁面 -->
    <a href="index.php">已有帳號，登入</a>
</div>
            <?php elseif ($_GET["action"] == "register_step2"): ?><!-- 如果網址帶有 ?action=register_step2，顯示註冊第二步的表單 -->
                <h1>註冊 - 第二步</h1>
                <form method="POST" enctype="multipart/form-data"> <!-- 註冊第二步的表單，支援檔案上傳 -->
    <div class="form-group">
        <label for="nickname">暱稱</label>
        <input type="text" name="nickname" required> <!-- 輸入暱稱（必填） -->
    </div>
    <div class="form-group">
        <label for="avatar">頭像</label>
        <input type="file" name="avatar" accept="image/*" onchange="previewAvatar(this)"> <!-- 上傳頭像圖片，只能選圖片檔，選完會預覽 -->
        <div class="img-tip">僅支援 JPG, JPEG, PNG, GIF 格式的圖片！</div> <!-- 圖片格式提示 -->
        <div id="avatar-preview" class="avatar-preview"></div> <!-- 頭像預覽區 -->
    </div>
    <button type="submit" name="register_step2">完成註冊</button> <!-- 送出表單按鈕 -->
</form>
                <div class="link-bottom-left">
    <!-- 提供一個「返回上一步」的連結，點擊後回到註冊第一步 -->
    <a href="index.php?action=register_step1">返回上一步</a>
</div>
            <?php endif; ?>
        <?php else: ?>
           <h1>歡迎，<?php echo htmlspecialchars($_SESSION["user"]["Nickname"]); ?>！</h1> <!-- 顯示歡迎訊息和使用者暱稱 -->
<form onsubmit="submitPostForm(this, event)" enctype="multipart/form-data"> <!-- 新增貼文的表單，支援圖片上傳 -->
    <h2>新增貼文</h2> <!-- 表單標題 -->
    <input type="text" name="title" placeholder="貼文標題" required> <!-- 貼文標題輸入框，必填 -->
    <textarea name="content" placeholder="貼文內容" required></textarea> <!-- 貼文內容輸入區，必填 -->
    <input type="file" name="post_images[]" multiple accept="image/*" style="margin-top:5px;"> <!-- 可以選多張圖片上傳，只接受圖片檔案 -->
    <button type="submit">發布貼文</button> <!-- 發布貼文按鈕 -->
</form>
            <hr>
            <h2>貼文列表</h2>
            <!-- 編輯貼文的彈窗（模態視窗），一開始隱藏 -->
<div id="editPostModal" class="modal-bg" style="display:none;" onclick="hideEditPostModal()">
    <!-- 彈窗內容區，點擊內容不會關閉彈窗 -->
    <div class="modal-box" onclick="event.stopPropagation()">
        <h3>編輯貼文</h3>
        <!-- 編輯貼文表單，送出時呼叫 submitEditPostForm -->
        <form id="edit-post-form" onsubmit="submitEditPostForm(event)">
            <!-- 隱藏欄位，存放要編輯的貼文ID -->
            <input type="hidden" id="editPostId" name="edit_post_id">
            <!-- 貼文標題輸入框 -->
            <input type="text" id="editPostTitle" name="edit_post_title" required placeholder="貼文標題">
            <!-- 貼文內容輸入區 -->
            <textarea id="editPostContent" name="edit_post_content" required style="height:100px" placeholder="貼文內容"></textarea>
            <!-- 按鈕區 -->
            <div class="modal-btns" style="margin-top:10px;">
                <!-- 儲存按鈕，送出表單 -->
                <button type="submit" class="blue-button">儲存</button>
                <!-- 取消按鈕，點擊關閉彈窗 -->
                <button type="button" class="cancel-btn" style="margin-left:5px;" onclick="hideEditPostModal()">取消</button>
            </div>
        </form>
    </div>
</div>
            <?php
// 查詢所有貼文、作者暱稱與頭像，以及每篇的按讚數和留言數
$postQuery = "
    SELECT Post.*, User.Nickname, User.Avatar, User.UID,
    (SELECT COUNT(*) FROM `Like` WHERE PostID = Post.PostID) AS LikeCount,
    (SELECT COUNT(*) FROM `Comment` WHERE PostID = Post.PostID) AS CommentCount
    FROM Post 
    JOIN User ON Post.AuthorUID = User.UID 
    ORDER BY Post.CreatedAt DESC";
$postResult = $conn->query($postQuery);
// 逐筆顯示每一篇貼文
while ($post = $postResult->fetch_assoc()) {
    // 決定頭像路徑（有上傳就用上傳的，否則用預設圖）
    $avatarPath = $post["Avatar"] ? "uploads/" . $post["Avatar"] : "default-avatar.png";
    echo "<div class='post'>";
    // 貼文上方：頭像、暱稱、發文時間
    echo "<div class='post-header'>";
    echo "<div style='display: flex; align-items: center;'>";
    // 作者頭像與個人頁連結
    echo "<a href='index.php?profile_uid=" . $post['UID'] . "'><img src='" . htmlspecialchars($avatarPath) . "' alt='頭像'></a>";
    // 作者暱稱與貼文時間
    echo "<div><strong>" . htmlspecialchars($post["Nickname"]) . "</strong><br><small>發布時間: " . $post["CreatedAt"] . "</small></div>";
    echo "</div>";
    // 判斷是否為作者本人
    $isAuthor = isset($_SESSION["user"]) && ($_SESSION["user"]["UID"] == $post["AuthorUID"]);
    // 判斷是否為管理員或小幫手
    $isAdminOrHelper = isset($_SESSION["user"]) && in_array($_SESSION["user"]["Role"], ["Admin", "Helper"]);
    // 作者才會看到「編輯」按鈕
    if ($isAuthor) {
        echo "<button type='button' class='edit-button' onclick='showEditPostModal(".$post["PostID"].",`".htmlspecialchars($post["Title"], ENT_QUOTES|ENT_SUBSTITUTE|ENT_HTML5)."`,".json_encode($post["Content"]).")'>編輯</button>";
    }
    // 作者或管理員才會看到「刪除」按鈕
    if ($isAuthor || $isAdminOrHelper) {
        echo "<form onsubmit='deletePost(".$post["PostID"].",this,event)' style='margin: 0;display:inline;'>";
        echo "<input type='hidden' name='post_id' value='" . $post["PostID"] . "'>";
        echo "<button type='submit' class='delete-button' style='margin-left:5px;' onclick='event.stopPropagation();'>刪除</button>";
        echo "</form>";
    }
    echo "</div>"; // 結束 post-header
    // 貼文標題，點擊可展開內容
    echo "<div class='post-title' onclick='togglePostContent(".$post["PostID"].")'>" . htmlspecialchars($post["Title"]) . "</div>";
    // 貼文內容（含圖片，預設收合）
    echo "<p class='post-content-pre' id='post-content-".$post["PostID"]."'>" . parsePostContentWithImages($post["Content"]) . "</p>";
    // 按讚數顯示（超過1000會顯示K）
    $likeCount = $post["LikeCount"];
    $likeDisplay = $likeCount >= 1000 ? round($likeCount / 1000, 1) . "K" : $likeCount;
    // 留言數
    $commentCount = $post["CommentCount"];
    // 按鈕區（按讚、留言、數量）
    echo "<div style='display: flex; align-items: center; margin-top: 10px;'>";
    echo "<button type='button' class='like-button' onclick='likePost(".$post["PostID"].",this,this.nextElementSibling)'>按讚</button>";
    echo "<span class='like-count'>讚: $likeDisplay</span>";
    echo "<button type='button' class='comment-toggle-btn' onclick='toggleCommentSection(".$post["PostID"].")'>留言</button>";
    echo "<span class='comment-count'>留言: $commentCount</span>";
    echo "</div>";
    // 留言區塊（預設隱藏）
    echo "<div id='comment-section-".$post["PostID"]."' class='comment-section'></div>";
    echo "</div>"; // 結束單一貼文
}
?>
        <?php endif; ?>
        <?php endif; ?>
    </div>
    <button id="goTopBtn" onclick="goTop()" title="回到頂部">↑</button><!-- 回到頂部按鈕，點擊時會執行 goTop() 函式讓頁面平滑捲到最上方 -->
</body>
</html>
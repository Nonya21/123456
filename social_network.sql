-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- 主機： 127.0.0.1
-- 產生時間： 2025-06-05 01:58:25
-- 伺服器版本： 10.4.32-MariaDB
-- PHP 版本： 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- 資料庫： `social_network`
--

-- --------------------------------------------------------

--
-- 資料表結構 `comment`
--

CREATE TABLE `comment` (
  `CommentID` int(11) NOT NULL,
  `Content` text NOT NULL,
  `CreatedAt` datetime DEFAULT current_timestamp(),
  `PostID` int(11) NOT NULL,
  `UserUID` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 傾印資料表的資料 `comment`
--

INSERT INTO `comment` (`CommentID`, `Content`, `CreatedAt`, `PostID`, `UserUID`) VALUES
(175, '魔術技巧~\n[IMG]68366635e395d_魔術技巧.png[/IMG]', '2025-05-28 09:26:13', 520, 31),
(176, '那刻夏:這一定不是我!!!\r\n', '2025-05-28 09:27:24', 520, 28),
(177, 'www\n[IMG]68366693626a3_497223464_17906077458113905_2769392708731997666_n.png[/IMG]\n[IMG]683666936320d_497227702_17906077449113905_7964722922761276084_n.png[/IMG]', '2025-05-28 09:27:47', 520, 31),
(178, ':)\n[IMG]68366aa3eb74c_刀.png[/IMG]', '2025-05-28 09:45:07', 525, 31),
(180, '\"刃\"天堂\n[IMG]6836b42292bbd_hq720_2.png[/IMG]', '2025-05-28 14:58:42', 542, 31),
(181, '呵呵，悲催', '2025-05-28 15:39:22', 531, 31),
(185, 'www\n[IMG]6836c00074308_知更鳥打榜.jpg[/IMG]', '2025-05-28 15:49:20', 528, 33),
(187, '哇~~好想去喔有沒有人要陪我去\r\n', '2025-06-03 16:57:03', 553, 24),
(189, '你請客阿', '2025-06-03 16:57:41', 553, 28),
(190, '所以你請嗎?\r\n', '2025-06-03 17:48:03', 553, 28),
(191, '人呢?\r\n說話!!!', '2025-06-03 17:48:21', 553, 28),
(192, '滾啊沒錢了!!!\r\n', '2025-06-04 09:24:25', 553, 24),
(193, '好冷門', '2025-06-04 09:26:01', 556, 31),
(194, '那我替你吃吧\r\n', '2025-06-04 09:26:34', 535, 24),
(195, '123\n[IMG]684012eabb670_0b54c23d1c046b3695074e4e85b6dc8e.png[/IMG]', '2025-06-04 17:33:30', 560, 31),
(196, '123\n[IMG]684012fa1a01c_images.png[/IMG]\n[IMG]684012fa1ac83_images刃.png[/IMG]\n[IMG]684012fa1b693_images治療.png[/IMG]\n[IMG]684012fa1bfd5_images星期日.png[/IMG]', '2025-06-04 17:33:46', 560, 31),
(197, '抽象的傢伙', '2025-06-04 17:45:07', 559, 33),
(198, '發神經\n[IMG]684015fd9d20b_AdobeStock_290162954.png[/IMG]', '2025-06-04 17:46:37', 559, 33),
(199, '發神經的傢伙\n[IMG]6840163ae40fc_images鏡.png[/IMG]', '2025-06-04 17:47:38', 559, 31),
(200, '圖很好，我徵用了', '2025-06-04 17:48:53', 537, 31),
(201, '拉帝奧教授與公司使節的互動，真是令人忍俊不禁\n[IMG]684017a44c296_物理.png[/IMG]', '2025-06-04 17:53:40', 552, 33),
(202, '123萬歲?', '2025-06-04 17:55:51', 560, 33);

-- --------------------------------------------------------

--
-- 資料表結構 `like`
--

CREATE TABLE `like` (
  `LikeID` int(11) NOT NULL,
  `PostID` int(11) NOT NULL,
  `UserUID` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 傾印資料表的資料 `like`
--

INSERT INTO `like` (`LikeID`, `PostID`, `UserUID`) VALUES
(163, 507, 24),
(165, 525, 28),
(172, 537, 28),
(173, 532, 28),
(174, 533, 28),
(175, 535, 28),
(176, 536, 28),
(177, 531, 28),
(179, 528, 28),
(180, 527, 28),
(181, 520, 28),
(182, 507, 28),
(183, 527, 31),
(184, 520, 31),
(190, 552, 34),
(191, 550, 34),
(192, 549, 34),
(193, 546, 34),
(194, 542, 34),
(195, 541, 34),
(197, 538, 34),
(199, 546, 24),
(200, 549, 24),
(201, 552, 24),
(202, 550, 24),
(203, 554, 24),
(204, 554, 28),
(205, 553, 28),
(207, 553, 1),
(208, 553, 24),
(209, 541, 24),
(210, 539, 24),
(211, 542, 35),
(215, 550, 31);

-- --------------------------------------------------------

--
-- 資料表結構 `music`
--

CREATE TABLE `music` (
  `MusicID` int(11) NOT NULL,
  `Name` varchar(255) NOT NULL,
  `File` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 傾印資料表的資料 `music`
--

INSERT INTO `music` (`MusicID`, `Name`, `File`) VALUES
(2, '世代的頌歌', '68400ef16f6e1_世代的頌歌.mp3'),
(5, '不虛此行', '68352e6c5e6d9_不虛此行.mp3'),
(7, '帽子怎麼尖尖的?', '68354c78de9cc_大黑塔的魔法廚房.mp3'),
(100, '無', '');

-- --------------------------------------------------------

--
-- 資料表結構 `post`
--

CREATE TABLE `post` (
  `PostID` int(11) NOT NULL,
  `Title` varchar(255) NOT NULL,
  `Content` text NOT NULL,
  `CreatedAt` datetime DEFAULT current_timestamp(),
  `AuthorUID` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 傾印資料表的資料 `post`
--

INSERT INTO `post` (`PostID`, `Title`, `Content`, `CreatedAt`, `AuthorUID`) VALUES
(507, '二周年快樂!!!', '崩鐵二周年生日快樂!\n', '2025-05-06 12:04:32', 1),
(520, '阿納克薩格拉斯.那刻夏', '那刻夏是一位風屬性智識命途角色，適合在對羣作戰環境下使用，在團隊中擔任輔助輸出的角色，為團隊的攻堅提供強大的幫助，那刻夏在資源足夠的情況下也能打出不俗的輸出，適合已有其他智識主C的玩家去組成配隊。\n[IMG]68353744535c5_1.jpg[/IMG]', '2025-05-27 11:53:40', 28),
(525, '羅剎', '「羅剎」定位和強度如何？\r\n\r\n羅剎，虛數屬性豐饒角色，集回血，解控，清除敵方增益為一體，技能機制簡單強力，兼具生存和功能性。\r\n羅剎作為第一個限五治療，直到當前版本使用率依然高居榜首。他的治療量基於攻擊力提升，數值極高，在玩家練度較低、想要攻克高難關卡的時候有著非常亮眼的表現，且能在回合外不耗點為隊友治療，極大緩解了隊伍生存壓力。0+0就是完全體，造價極低，對於零氪、或者剛入坑的萌新而言，羅剎是性價比最高的選擇，非常值得抽取。\n[IMG]68353c7d00df7_2.jpg[/IMG]', '2025-05-27 12:15:56', 28),
(527, '羅剎 復刻???', '就業方向：高耗點主C隊+全環境\r\n\r\n\r\n高耗點主C隊：羅剎可以進行不耗點治療，搭配高耗點主C時（比如丹恆·飲月、青雀等），不僅解決了隊伍生存問題，還能節省戰技點，同時普攻產點，輔助主C全力輸出。   \r\n全環境：作為帶解控的生存位，羅剎可以適配當前任何隊伍。羅剎自身的高額治療量以及自動治療機制、結界回血機制，使他可以輕松面對各種高強度環境。\n[IMG]68353cf86437e_3.jpg[/IMG]', '2025-05-27 12:18:00', 28),
(528, '銀河大明星', '知更鳥小姐的巡迴演出\r\n在銀河中孤獨搖擺\r\n使一顆心免於哀傷\r\n若我不曾見過太陽\r\n感謝知更鳥小姐贊助本網站使用\n[IMG]68354ead89225_477057865_642819488260245_6946964869073927973_n.jpg[/IMG]', '2025-05-27 13:33:33', 1),
(529, '第五人格', '皮膚', '2025-05-28 09:26:02', 27),
(530, '第五人格皮膚', '好想要啊啊啊啊啊啊啊啊啊啊啊啊!\n[IMG]6836668834d0c_4148273.jpg[/IMG]', '2025-05-28 09:27:36', 27),
(531, '崩鐵', '課程檢核時\n[IMG]6836671aed8f6_下載.jpg[/IMG]', '2025-05-28 09:30:02', 28),
(532, '崩鐵', '聽到要小考時\n[IMG]6836674f4cc1c_images.jpg[/IMG]', '2025-05-28 09:30:55', 28),
(533, '此網站更燈進去時', '我們的精神狀態\n[IMG]6836678eece25_下載 (1).jpg[/IMG]', '2025-05-28 09:31:58', 28),
(535, '崩鐵', '聽到有好吃的，但是要出門\n[IMG]683667deb74af_images (1).jpg[/IMG]', '2025-05-28 09:33:18', 28),
(536, '鴨子', '嘎嘎嘎\n[IMG]683667f4a3b8e_7bfcf85ecbe5871c761f5d857e1g33z5.jpg[/IMG]', '2025-05-28 09:33:40', 27),
(537, '崩鐵', '窩窩窩需要ㄚㄚㄚㄚㄚ\n[IMG]6836681395a6d_images (2).jpg[/IMG]', '2025-05-28 09:34:11', 28),
(538, '崩鐵', '有人唱歌時，還走調!!!!\n[IMG]683668790e3b5_下載 (2).jpg[/IMG]', '2025-05-28 09:35:53', 28),
(539, '好想要', '好想要黯，好想要啊啊啊啊啊啊啊啊啊啊啊啊啊啊啊!\n[IMG]6836690948c35_黯.jpg[/IMG]', '2025-05-28 09:38:17', 27),
(541, '崩鐵 砂金', '「愿母神三度为你阖眼……」\n「令你的血脉永远鼓动……」\n「路途永远坦然……」\n「...诡计永不败露」 \n[IMG]68366be176650_下載 (3).jpg[/IMG]', '2025-05-28 09:50:25', 28),
(542, '小蜜蜂', '你看今天又養了這麼多隻小蜜蜂\n[IMG]683671ba65b40_圖片1.png[/IMG]', '2025-05-28 10:15:22', 24),
(546, '阿哈~', '歡愉的令使駕到\n[IMG]6836b525b4d06_49290c7ac74a8fc7c470c513c10ef0c3.png[/IMG]\n[IMG]6836b525b57d7_ebc341c02cf7754b310e0fc2468a6d7f_4769903161153084673.png[/IMG]', '2025-05-28 15:03:01', 31),
(549, '規則就是用來打破的', '銀河球棒俠!\n[IMG]683cc5ab901dc_640.png[/IMG]', '2025-05-28 15:26:48', 31),
(550, '社會的理想制度應當是七休日', '星期日的隔天是又一個星期日\n世界需要七休日', '2025-05-28 15:52:06', 34),
(552, '博識學會', '庸人自擾\n[IMG]683cd84f15c2e_images醫生.png[/IMG]', '2025-06-02 06:46:39', 35),
(553, '日本壽司郎 ×《崩壞：星穹鐵道》合作即將登場？官方 X 貼文出現疑似「鐘錶小子」角色', '日本連鎖迴轉壽司店「壽司郎（あきんどスシロー）」的官方 X 於今日公開新貼文，疑似是預告與銀河冒險策略 RPG《崩壞：星穹鐵道》的合作即將展開。\n\n投稿中出現了以粗線條動畫風格描繪的人物手部圖片，內文則是搭配鐘錶的「滴答，滴答…（チクタク、チクタク…）」聲。\n\n　　綜合上述特徵，有不少日本網友猜測這個人物或許是來自《崩壞：星穹鐵道》的「鐘錶小子」，而「鐘錶小子」也在今日登上日本 X 的趨勢。\n\n值得一提的是，在今年 1 月時日本壽司郎就已經預告會於 2025 年春季實施與《崩壞：星穹鐵道》的合作，以時間上來看今天已經相當接近當初預定的合作時間。\n ', '2025-06-03 13:42:35', 32),
(554, '匹諾康尼', '流螢?\n[IMG]683e906b97af7_images刃.png[/IMG]', '2025-06-03 14:04:27', 1),
(556, '第五人格', '演绎之星系列【稀世时装】宿伞之魂-粲然的北辰\n[IMG]683eb958d5041_6256452c-168c-4d1d-9d87-fd042a7a04f7.jpg[/IMG]', '2025-06-03 16:59:04', 27),
(559, '崩鐵玩家精神狀態', '抽象\n[IMG]68401126c6b3d_images星期日2.png[/IMG]\n[IMG]68401126c768f_images莫名其妙.png[/IMG]\n[IMG]68401126c8361_吹捧的通稿.png[/IMG]\n[IMG]68401126c8d85_我就爛.png[/IMG]\n[IMG]68401126c992e_所以我出手了.png[/IMG]\n[IMG]68401126ca557_崇高道德大禮包.png[/IMG]\n[IMG]68401126cb193_魔術技巧.png[/IMG]', '2025-06-04 17:25:58', 31),
(560, '123', '123', '2025-06-04 17:32:31', 31);

-- --------------------------------------------------------

--
-- 資料表結構 `user`
--

CREATE TABLE `user` (
  `UID` int(11) NOT NULL,
  `Nickname` varchar(50) NOT NULL,
  `Avatar` varchar(255) DEFAULT NULL,
  `Email` varchar(100) NOT NULL,
  `Password` varchar(255) NOT NULL,
  `Role` enum('Admin','Helper','User') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 傾印資料表的資料 `user`
--

INSERT INTO `user` (`UID`, `Nickname`, `Avatar`, `Email`, `Password`, `Role`) VALUES
(1, 'Nonya', '683d985ad05fe_153_20250326163418.png', '112534213@stu.ukn.edu.tw', '$2y$10$LzCkow3xm6t5LT8Zk0NZW.1l701nQ6jPq27bkZs/yHsKWL5yezqDe', 'Admin'),
(24, '123', '683538159ce5b_下載.jpg', '112534218@stu.ukn.edu.tw', '$2y$10$qS4Dp27BoqYzGvblWDVFE.XwHwwgPvR9cYYzTT8YTOGXVgDnpv7oC', 'Helper'),
(27, '燒餅在線打油條', '683666df90c09_下載.jpg', '112534212@stu.ukn.edu.tw', '$2y$10$T6jT2kSRk9YF7ZXZPBjvzOl6l1bH1Oa7DVNjnhEwt1yGcqCdlc7da', 'User'),
(28, '油條在線打燒餅', '683537b08d13d_1.jpg', 'pinpin97814@gmail.com', '$2y$10$LE4ui43C.FzrU0mleDnmDeT1LiQYQcTCP089934SLYVGb1xR9Y20.', 'Helper'),
(29, '123', '6819887ab8963_163418.png', '1@stu.ukn.edu.tw', '$2y$10$CZTP8B8oKKA3zGh64FVE3.rqxd0i2a0aNgG00ym9YYvd8l7PdSlCu', 'Helper'),
(31, '周休七日立即執行!', '6836666f97d3f_images星期日.png', '1@123', '$2y$10$zLIa0Tpa75868FelcIH8p.cfKLXmDuMfMyKKziX3hKDhjKic3aAqC', 'User'),
(32, '米忽悠', '68366c53d8526_images (3).jpg', '112534216@stu.ukn.edu.tw', '$2y$10$TkCAPYAf2MqtliATNywpeOe7ynNQoZU5XheVTzTXKnK5HEe2MQDKq', 'Admin'),
(33, '知更鳥小姐的頭號粉絲', '6836beb357fbe_www.png', '123@123', '$2y$10$Ecr3bndv4FqGolqETLPj4.rCUJQaylmAtpYFPgA2/ddYWysOSKpVa', 'User'),
(34, '周休七日', '6836c04181218_owdzKQCDGAW7BeLxgIiYjQDBGf8VsTqiAABAdI~tplv-dy-360p.png', '456@456', '$2y$10$/YZf6cGY49XojcuHSaZ8/eJVbkYZf46H1sYnsfap2B55HdbtYYdRe', 'User'),
(35, '黑塔粉絲', '683cd82216018_轉圈圈咯.gif', '789@789', '$2y$10$oWQDxm2wqTfJQ.MnO1rYpeJPogp8ALBkJkDoPCakAhDka9k9DGePe', 'User');

--
-- 已傾印資料表的索引
--

--
-- 資料表索引 `comment`
--
ALTER TABLE `comment`
  ADD PRIMARY KEY (`CommentID`),
  ADD KEY `PostID` (`PostID`),
  ADD KEY `UserUID` (`UserUID`);

--
-- 資料表索引 `like`
--
ALTER TABLE `like`
  ADD PRIMARY KEY (`LikeID`),
  ADD KEY `PostID` (`PostID`),
  ADD KEY `UserUID` (`UserUID`);

--
-- 資料表索引 `music`
--
ALTER TABLE `music`
  ADD PRIMARY KEY (`MusicID`);

--
-- 資料表索引 `post`
--
ALTER TABLE `post`
  ADD PRIMARY KEY (`PostID`),
  ADD KEY `AuthorUID` (`AuthorUID`);

--
-- 資料表索引 `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`UID`),
  ADD UNIQUE KEY `Email` (`Email`);

--
-- 在傾印的資料表使用自動遞增(AUTO_INCREMENT)
--

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `comment`
--
ALTER TABLE `comment`
  MODIFY `CommentID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=203;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `like`
--
ALTER TABLE `like`
  MODIFY `LikeID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=216;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `music`
--
ALTER TABLE `music`
  MODIFY `MusicID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=104;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `post`
--
ALTER TABLE `post`
  MODIFY `PostID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=562;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `user`
--
ALTER TABLE `user`
  MODIFY `UID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- 已傾印資料表的限制式
--

--
-- 資料表的限制式 `comment`
--
ALTER TABLE `comment`
  ADD CONSTRAINT `comment_ibfk_1` FOREIGN KEY (`PostID`) REFERENCES `post` (`PostID`) ON DELETE CASCADE,
  ADD CONSTRAINT `comment_ibfk_2` FOREIGN KEY (`UserUID`) REFERENCES `user` (`UID`) ON DELETE CASCADE;

--
-- 資料表的限制式 `like`
--
ALTER TABLE `like`
  ADD CONSTRAINT `like_ibfk_1` FOREIGN KEY (`PostID`) REFERENCES `post` (`PostID`) ON DELETE CASCADE,
  ADD CONSTRAINT `like_ibfk_2` FOREIGN KEY (`UserUID`) REFERENCES `user` (`UID`) ON DELETE CASCADE;

--
-- 資料表的限制式 `post`
--
ALTER TABLE `post`
  ADD CONSTRAINT `post_ibfk_1` FOREIGN KEY (`AuthorUID`) REFERENCES `user` (`UID`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

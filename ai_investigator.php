<?php
header("Content-Type: text/plain");

$host = 'dpg-d6bllv2li9vc73dkbbhg-a.oregon-postgres.render.com';
$db   = 'falconai_db_k76d';
$user = 'falconai_db_k76d_user';
$pass = 'sYYhitKQLAMwkELMc5V6SdKRWvFBjOZC';

try {
    $dsn = "pgsql:host=$host;dbname=$db";
    $pdo = new PDO($dsn, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
  
    $stmt = $pdo->query("SELECT id, name FROM channels");
    $channels = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($channels)) {
        die("The 'channels' table is empty. Please insert channels first!");
    }

    foreach ($channels as $channel) {
        $name = $channel['name'];
        $id = $channel['id'];
        
        $content = "";
        $nameLower = strtolower($name);

        if (preg_match('/news|lajme|24|world|bbc|cnn|dw|alhurra|current/i', $nameLower)) {
            $content = "Breaking News, International News, Politics, Current Affairs, Journalism";
        } elseif (preg_match('/music|vevo|deluxe|dance|rap|pop|clubbing|dj/i', $nameLower)) {
            $content = "Music Videos, Entertainment, Hits, Pop Culture, Concerts";
        } elseif (preg_match('/kids|kika|toon|ric|lego|caillou|animation|disney/i', $nameLower)) {
            $content = "Cartoons, Children Programming, Animation, Family Friendly, Educational";
        } elseif (preg_match('/nasa|science|space|discovery|knowledge|documentary/i', $nameLower)) {
            $content = "Science, Space Exploration, Astronomy, Technology, Documentaries";
        } elseif (preg_match('/sport|racing|tennis|cornhole|fanDuel|lacrosse|football/i', $nameLower)) {
            $content = "Live Sports, Athletics, Competition, Racing, Sports Highlights";
        } elseif (preg_match('/rtv|drita|pendimi|islam|shqip/i', $nameLower)) {
            $content = "Albanian Language, Kosovo TV, Cultural, Religious, Balkan News";
        } elseif (preg_match('/fashion|jewelry|shop|qvc|hsn/i', $nameLower)) {
            $content = "Shopping, Fashion, Lifestyle, Jewelry, Home Trends";
        } elseif (preg_match('/cinema|film|movie|retro|classic/i', $nameLower)) {
            $content = "Movies, Cinema, Classic Films, Hollywood, Entertainment";
        } else {
            $content = "General Entertainment, Live Broadcast, Variety Shows";
        }

        $update = $pdo->prepare("UPDATE channels SET live_content = :c, last_updated = NOW() WHERE id = :id");
        $update->execute(['c' => $content, 'id' => $id]);
        
        echo "Investigated: [{$name}] -> {$content}\n";
    }

    echo "\n--- AI Content Investigation Completed Successfully! ---";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}

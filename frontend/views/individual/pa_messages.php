<?php
// messages.php
// You can later add PHP logic here (sessions, database, login, etc.)
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Food Bank - Messages</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --primary-green: #417d3b;
            --sidebar-width: 280px;
            --bg-light: #ffffff;
            --text-dark: #333;
            --text-gray: #888;
        }

        body {
            font-family: 'Inter', sans-serif;
            margin: 0;
            display: flex;
            background-color: var(--bg-light);
            height: 100vh;
            overflow: hidden;
        }

        .sidebar {
            width: var(--sidebar-width);
            border-right: 1px solid #f0f0f0;
            display: flex;
            flex-direction: column;
            padding: 30px 20px;
            background: white;
            z-index: 10;
        }

        .logo-section {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 40px;
            padding-left: 10px;
        }

        .logo-section h2 {
            color: var(--primary-green);
            margin: 0;
            font-size: 1.5rem;
            line-height: 1;
        }

        .user-profile-nav {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 30px;
            padding: 10px;
            border-bottom: 1px solid #f9f9f9;
        }

        .nav-list {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .nav-item {
            display: flex;
            align-items: center;
            padding: 12px 15px;
            color: #555;
            text-decoration: none;
            border-radius: 10px;
            font-weight: 500;
            transition: 0.2s;
        }

        .nav-item i {
            width: 25px;
            font-size: 1.1rem;
            margin-right: 12px;
            text-align: center;
        }

        .nav-item.active {
            background-color: var(--primary-green);
            color: white;
        }

        .nav-item:hover:not(.active) {
            background-color: #f0fdf4;
            color: var(--primary-green);
        }

        .main-content {
            flex: 1;
            overflow-y: auto;
            position: relative;
        }

        .green-bg-header {
            background-color: var(--primary-green);
            height: 200px;
            width: 100%;
            position: absolute;
            top: 0;
            left: 0;
            z-index: 1;
        }

        .content-container {
            position: relative;
            z-index: 2;
            padding: 40px;
            max-width: 1000px;
            margin: 0 auto;
        }

        .welcome-card {
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(15px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 20px;
            padding: 40px;
            color: white;
            margin-bottom: 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .messages-header h2 { margin: 0; font-size: 1.8rem; }
        .messages-header p { color: var(--text-gray); margin: 5px 0 25px; }

        .message-card {
            background: white;
            padding: 20px 25px;
            margin-bottom: 15px;
            border-radius: 18px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 4px 20px rgba(0,0,0,0.04);
            cursor: pointer;
        }

        .profile-group { display: flex; align-items: center; gap: 15px; }

        .profile-image {
            width: 55px;
            height: 55px;
            border-radius: 50%;
            object-fit: cover;
            background-color: #eee;
        }

        .profile-name { font-weight: 700; margin: 0; color: var(--text-dark); }
        .last-msg { margin: 4px 0 0; color: var(--text-gray); }
        .time-stamp { color: #bbb; font-size: 0.85rem; }

        #chat-page {
            display: none;
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            background: white;
            z-index: 5000;
            flex-direction: column;
        }

        .chat-header, .chat-footer {
            padding: 15px 25px;
            border-bottom: 1px solid #eee;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .chat-footer {
            border-top: 1px solid #eee;
            border-bottom: none;
        }

        .chat-footer input {
            flex: 1;
            padding: 15px;
            border-radius: 30px;
            border: 1px solid #ddd;
        }

        .send-btn {
            background: var(--primary-green);
            color: white;
            border: none;
            padding: 0 25px;
            border-radius: 30px;
            cursor: pointer;
        }
    </style>
</head>

<body>

<div class="sidebar">
    <div class="logo-section">
        <h2>Food Bank <span style="font-weight:300; font-size:0.8rem; display:block; color:#666;">APP</span></h2>
    </div>

    <div class="user-profile-nav">
        <img src="https://i.pravatar.cc/150?u=gereuel" class="profile-image">
        <div>
            <p class="profile-name">Gereuel Brillantes</p>
            <p style="font-size: 0.75rem; color: var(--text-gray); margin: 0;">gereuel@gnc.edu.ph</p>
        </div>
    </div>

    <nav class="nav-list">
        <a href="pa_home_page.php" class="nav-item"><i class="fa-regular fa-heart"></i> Food Banks</a>
        <a href="pa_messages.php" class="nav-item active"><i class="fa-solid fa-comment-dots"></i> Messages</a>
        <a href="pa_donors.php" class="nav-item"><i class="fa-solid fa-users"></i> Donors</a>
        <a href="#" class="nav-item"><i class="fa-solid fa-hand-holding-heart"></i> Donation</a>
        <a href="#" class="nav-item"><i class="fa-regular fa-user"></i> Account</a>
    </nav>
</div>

<div class="main-content">
    <div class="green-bg-header"></div>

    <div class="content-container">
        <div class="welcome-card">
            <div>
                <h1 style="margin:0;">Welcome back, Gereuel!</h1>
                <p style="margin:8px 0 0;">Let's make a difference today</p>
            </div>
        </div>

        <div class="messages-header">
            <h2>Messages</h2>
            <p>Your conversation and updates</p>
        </div>

        <div class="message-card" onclick="openChat('Trixia Rivera','https://i.pravatar.cc/150?u=trixia')">
            <div class="profile-group">
                <img src="https://i.pravatar.cc/150?u=trixia" class="profile-image">
                <div>
                    <p class="profile-name">Trixia Rivera</p>
                    <p class="last-msg">Good morning, Partner!</p>
                </div>
            </div>
            <span class="time-stamp">2hrs ago</span>
        </div>
    </div>
</div>

<section id="chat-page">
    <div class="chat-header">
        <button onclick="closeChat()">←</button>
        <img id="chat-profile-img" class="profile-image">
        <p id="chat-profile-name"></p>
    </div>

    <div class="chat-body"></div>

    <div class="chat-footer">
        <input type="text" placeholder="Write a message...">
        <button class="send-btn">Send</button>
    </div>
</section>

<script src="messages.js"></script>

</body>
</html>
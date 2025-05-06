<?php

$update = 0;
$ndisp = 0;
$snum = 0;
$sall = 1;
$sdetail = 1;

if (isset($_GET["number_displayed"])) {
    $update = 1;

    $ndisp_raw = $_GET["number_displayed"] ?? 0;
    $snum = isset($_GET["show_number"]) ? htmlspecialchars($_GET["show_number"]) : "0";
    $sall = isset($_GET["show_all"]) ? htmlspecialchars($_GET["show_all"]) : "1";
    $sdetail = isset($_GET["show_detailed"]) ? htmlspecialchars($_GET["show_detailed"]) : "1";

    if (is_numeric($ndisp_raw)) {
        $ndisp = intval($ndisp_raw);
    } else {
        $ndisp = 0;
    }

    $snum = ($snum == "1") ? "1" : "0";
    $sall = ($sall == "1") ? "1" : "0";
    $sdetail = ($sdetail == "1") ? "1" : "0";

    $expiretime = 2147483645;
    $cookie_path = "/";
    setcookie("display-data[number-displayed]", (string)$ndisp, $expiretime, $cookie_path);
    setcookie("display-data[show-number]", $snum, $expiretime, $cookie_path);
    setcookie("display-data[show-all]", $sall, $expiretime, $cookie_path);
    setcookie("display-data[show-detailed]", $sdetail, $expiretime, $cookie_path);

} else {
    if (isset($_COOKIE['display-data']) && is_array($_COOKIE['display-data'])) {
        $cookie_data = $_COOKIE['display-data'];

        $ndisp = intval(htmlspecialchars($cookie_data['number-displayed'] ?? 0));
        $snum_cookie = htmlspecialchars($cookie_data['show-number'] ?? "0");
        $sall_cookie = htmlspecialchars($cookie_data['show-all'] ?? "1");
        $sdetail_cookie = htmlspecialchars($cookie_data['show-detailed'] ?? "1");

        $snum = ($snum_cookie == "1") ? "1" : "0";
        $sall = ($sall_cookie == "1") ? "1" : "0";
        $sdetail = ($sdetail_cookie == "1") ? "1" : "0";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Supermon Display Settings</title>
    <link type="text/css" rel="stylesheet" href="supermon.css">
    <style>
        body {
            background-color: black;
            color: white;
            font-family: sans-serif;
            margin: 0;
            padding: 20px;
        }
        a {
            color: #8af;
        }
        p, h3, label, td {
            color: white;
        }
        input[type="text"] {
            background-color: #333;
            color: white;
            border: 1px solid #777;
            padding: 5px;
            transform: scale(1.5);
            margin-left: 20px;
            border-radius: 4px;
        }
        input[type="radio"] {
            transform: scale(2);
            margin-right: 8px;
            vertical-align: middle;
        }
        .submit-large {
            padding: 10px 20px;
            font-size: 1em;
            background-color: #444;
            color: white;
            border: 1px solid #888;
            cursor: pointer;
            border-radius: 8px;
            transition: background-color 0.2s ease;
        }
        .submit-large:hover {
            background-color: #555;
        }
        table {
            margin-top: 20px;
            font-size: 20px;
            border-collapse: collapse;
            width: auto;
        }
        td {
            padding-bottom: 20px;
            vertical-align: top;
        }
        td:last-child {
            padding-bottom: 0;
        }
        .button-row td {
            padding-top: 20px;
            padding-bottom: 0;
            text-align: center;
        }
        .title {
            font-size: 1.5em;
            margin-bottom: 25px;
            font-weight: bold;
            text-align: center;
        }
        .radio-label {
            display: inline-block;
            margin-right: 25px;
            margin-top: 8px;
        }
        .update-notice {
            color: lightgreen;
            margin-top: 10px;
            margin-bottom: 15px;
            text-align: center;
            font-weight: bold;
        }
    </style>
    <script>
        function refreshParent() {
            if (window.opener && !window.opener.closed) {
                try {
                    window.opener.location.reload();
                } catch (e) {
                    console.error("Could not reload opener window:", e);
                }
            }
        }

        <?php if ($update === 1): ?>
        document.addEventListener('DOMContentLoaded', (event) => {
            refreshParent();
        });
        <?php endif; ?>
    </script>
</head>
<body>
    <p class="title">Supermon Display Settings</p>

    <?php if ($update === 1): ?>
        <p class="update-notice">Settings Updated!</p>
    <?php endif; ?>

    <form action="display-config.php" method="get" style="display: flex; justify-content: center;">
        <table border="0" cellspacing="0" cellpadding="0">
            <tr>
                <td valign="top">
                    Display Detailed View:<br>
                    <label class="radio-label">
                        <input type="radio" name="show_detailed" value="1" <?php echo ($sdetail == "1") ? 'checked' : ''; ?>> YES
                    </label>
                    <label class="radio-label">
                        <input type="radio" name="show_detailed" value="0" <?php echo ($sdetail == "0") ? 'checked' : ''; ?>> NO
                    </label>
                </td>
            </tr>
            <tr>
                <td valign="top">
                    Show the number of connections (Displays x of y):<br>
                    <label class="radio-label">
                        <input type="radio" name="show_number" value="1" <?php echo ($snum == "1") ? 'checked' : ''; ?>> YES
                    </label>
                    <label class="radio-label">
                        <input type="radio" name="show_number" value="0" <?php echo ($snum == "0") ? 'checked' : ''; ?>> NO
                    </label>
                </td>
            </tr>
            <tr>
                <td valign="top">
                    Show ALL Connections (NO omits NEVER Keyed):<br>
                    <label class="radio-label">
                        <input type="radio" name="show_all" value="1" <?php echo ($sall == "1") ? 'checked' : ''; ?>> YES
                    </label>
                    <label class="radio-label">
                        <input type="radio" name="show_all" value="0" <?php echo ($sall == "0") ? 'checked' : ''; ?>> NO
                    </label>
                </td>
            </tr>
            <tr>
                <td valign="top">
                    Maximum Number of Connections to Display in Each Node (0=ALL):<br><br>
                    <input type="text" name="number_displayed" value="<?php echo $ndisp; ?>" maxlength="4" size="3">
                </td>
            </tr>
            <tr class="button-row">
                <td>
                    <input type="submit" class="submit-large" value="Update Settings">
                    <input type="button" class="submit-large" value="Close Window" onclick="self.close()">
                </td>
            </tr>
        </table>
    </form>
</body>
</html>

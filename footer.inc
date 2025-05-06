<div class="clearer"></div>

<div id="footer">
<?php
if (isset($_SESSION['sm61loggedin']) && $_SESSION['sm61loggedin'] === true) {
?>
    <script>
        function OpenExample() {
            window.open('http://anyurl.com');
        }
    </script>
    <!--
    <input type="button" class="submit" value="Example" onclick="OpenExample()">
    -->
<?php
}

// Display maintainer information
if (!empty($MAINTAINER)) {
    echo "<b>System maintained by:   <i>" . htmlspecialchars($MAINTAINER) . "</i></b>";
} else {
    echo "<b>System maintained by:   <i>" . htmlspecialchars($NAME) . ",   " . htmlspecialchars($CALL) . "</i></b>";
}
?>
</div>
<br><br>
</body>
</html>

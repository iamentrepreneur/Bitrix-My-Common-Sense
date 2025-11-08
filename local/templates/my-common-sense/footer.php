<?
if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
	die();
?>

</div>
<script>
    (function() {

        "use strict";

        const toggles = document.querySelectorAll(".cmn-toggle-switch");
        const burgerMenu = document.getElementById("burger-menu");

        for (let i = toggles.length - 1; i >= 0; i--) {
            const toggle = toggles[i];
            toggleHandler(toggle);
        }

        function toggleHandler(toggle) {
            toggle.addEventListener( "click", function(e) {
                e.preventDefault();
                (this.classList.contains("active") === true) ? this.classList.remove("active") : this.classList.add("active");
                burgerMenu.classList.toggle('active');
            });
        }

    })();
</script>

	</body>
</html>
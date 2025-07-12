<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://code.jquery.com/ui/1.13.0/jquery-ui.min.js"></script>
<script src="/assets/dist/js/bootstrap.bundle.min.js"></script>

<!-- DataTables Plugin -->
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<!-- sweet alert  -->
<script src="https://unpkg.com/sweetalert/dist/sweetalert.min.js"></script>
<!-- summer note  -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/summernote/0.8.9/summernote-bs4.js"></script>
<!-- Select2 JS -->
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>


<script>
    $(document).ready(function () {
        let activeMenuItem = $(document).find('.menu-acitve');
        if (activeMenuItem.length) {
            let position = activeMenuItem.offset().top - 80;
            $('.sidebar').animate({
                scrollTop: position
            }, 0);
        }

        $('.select2').select2();
    });

    showPreloader();
    $(window).on('load', function () {
        hidePreloader();
    });

    function showPreloader() {
        $('.preloader').show();
    }

    function hidePreloader() {
        $('.preloader').fadeOut('fast', function () {
            $(this).remove();
        });
    }

    window.onload = function () {
        const sidebar = document.querySelector(".sidebar");
        const closeBtn = document.querySelector(".btn-menu-openClose");
        const searchBtn = document.querySelector(".bx-search");

        if (window.innerWidth >= 991) {
            sidebar.classList.add("open");
            setLargeScreenStyles();
        }

        closeBtn.addEventListener("click", function () {
            sidebar.classList.toggle("open");
            menuBtnChange();
        });

        function menuBtnChange() {
            if (sidebar.classList.contains("open")) {
                setLargeScreenStyles();
            } else {
                setSmallScreenStyles();
            }
        }

        function setLargeScreenStyles() {
            sidebar.style.width = "250px";
            closeBtn.classList.replace("menu", "menu-alt-right");
            $('#logo-name').removeClass('d-none');
            $('#logo-full-img').removeClass('d-none');
            $('#logo-img').addClass('d-none');
            $('#profile_img').addClass('pl-5');
            $('#profile_img').removeClass('pl-3');
            $('#close-icon').removeClass('d-none');
            $('#open-icon').addClass('d-none');
            $('.btn-menu-openClose').css('left', 235);
            $('#logout_btn').addClass('d-block');
            $('#logout_btn').removeClass('d-none');
            $('#logout_icon').removeClass('d-block');
            $('#logout_icon').addClass('d-none');
            $('.content-wrapper').css('padding', '.75rem .2rem');
        }

        function setSmallScreenStyles() {
            sidebar.style.width = "78px";
            closeBtn.classList.replace("menu-alt-right", "menu");
            $('#logo-name').addClass('d-none');
            $('#logo-full-img').addClass('d-none');
            $('#logo-img').removeClass('d-none');
            $('#profile_img').addClass('pl-3');
            $('#profile_img').removeClass('pl-5');
            $('#close-icon').addClass('d-none');
            $('#open-icon').removeClass('d-none');
            $('.btn-menu-openClose').css('left', 63);
            $('.content-wrapper').css('padding', '.75rem .2rem');
            $('#logout_btn').removeClass('d-block');
            $('#logout_btn').addClass('d-none');
            $('#logout_icon').addClass('d-block');
        }

        window.addEventListener("resize", function () {
            if (window.innerWidth >= 991) {
                sidebar.classList.add("open");
                setLargeScreenStyles();
            } else {
                sidebar.classList.remove("open");
                setSmallScreenStyles();
            }
        });
    };
</script>
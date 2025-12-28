<?php
// $settings array should already be loaded
$siteName        = htmlspecialchars($setting->get('site_name')) ?? 'Influence';
$developerName   = $settings['developer_name'] ?? 'Code With Nikhil';
$developerUrl    = $settings['developer_url'] ?? 'https://codewithnikhil.in/';
$enableChat      = !empty($settings['enable_admin_chat']);
?>

<footer class="footer_part">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="footer_iner text-center">
                    <p>
                        <?= date('Y'); ?> © <?= htmlspecialchars($siteName); ?> —
                        Designed by
                        <a href="<?= htmlspecialchars($developerUrl); ?>" target="_blank">
                            <?= htmlspecialchars($developerName); ?>
                        </a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</footer>

<?php if ($enableChat): ?>
<!-- Admin Chat Popup -->
<div class="CHAT_MESSAGE_POPUPBOX" aria-label="Admin Chat">
    <div class="CHAT_POPUP_HEADER">
        <button class="MSEESAGE_CHATBOX_CLOSE" aria-label="Close chat">
            ✕
        </button>

        <h3>Chat with Support</h3>

        <div class="Chat_Listed_member">
            <ul>
                <?php for ($i = 1; $i <= 5; $i++): ?>
                    <li>
                        <img src="<?= BASE_URL ?>assets/img/staf/<?= $i ?>.png" alt="Staff <?= $i ?>">
                    </li>
                <?php endfor; ?>
                <li>
                    <span class="more_member_count">90+</span>
                </li>
            </ul>
        </div>
    </div>

    <div class="CHAT_POPUP_BODY">
        <p class="mesaged_send_date"><?= date('l, d F'); ?></p>

        <div class="CHATING_SENDER">
            <img src="<?= BASE_URL ?>assets/img/staf/1.png" alt="Support">
            <div class="SEND_SMS_VIEW">
                <p>Welcome! How can we help you?</p>
            </div>
        </div>
    </div>

    <div class="CHAT_POPUP_BOTTOM">
        <div class="input-group">
            <input type="text" class="form-control" placeholder="Type a message…">
            <button class="btn btn-primary" type="button">Send</button>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Back to Top -->
<div id="back-top">
    <a href="#" aria-label="Back to top">
        <i class="ti-angle-up"></i>
    </a>
</div>


    <script src="assets/js/jquery1-3.4.1.min.js"></script>

    <script src="assets/js/popper1.min.js"></script>

    <!-- <script src="assets/js/bootstrap.min.js"></script> -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@3.3.7/dist/js/bootstrap.min.js" integrity="sha384-Tc5IQib027qvyjSMfHjOMaLkfuWVxZxUPnCJA7l2mCWNIpG9mGCD8wGNIcPD7Txa" crossorigin="anonymous"></script>


    <script src="assets/js/metisMenu.js"></script>

    <script src="assets/vendors/count_up/jquery.waypoints.min.js"></script>

    <script src="assets/vendors/chartlist/Chart.min.js"></script>

    <script src="assets/vendors/count_up/jquery.counterup.min.js"></script>

    <script src="assets/vendors/niceselect/js/jquery.nice-select.min.js"></script>

    <script src="assets/vendors/owl_carousel/js/owl.carousel.min.js"></script>

    <script src="assets/vendors/datatable/js/jquery.dataTables.min.js"></script>
    <script src="assets/vendors/datatable/js/dataTables.responsive.min.js"></script>
    <script src="assets/vendors/datatable/js/dataTables.buttons.min.js"></script>
    <script src="assets/vendors/datatable/js/buttons.flash.min.js"></script>
    <script src="assets/vendors/datatable/js/jszip.min.js"></script>
    <script src="assets/vendors/datatable/js/pdfmake.min.js"></script>
    <script src="assets/vendors/datatable/js/vfs_fonts.js"></script>
    <script src="assets/vendors/datatable/js/buttons.html5.min.js"></script>
    <script src="assets/vendors/datatable/js/buttons.print.min.js"></script>

    <script src="assets/vendors/datepicker/datepicker.js"></script>
    <script src="assets/vendors/datepicker/datepicker.en.js"></script>
    <script src="assets/vendors/datepicker/datepicker.custom.js"></script>
    <script src="assets/js/chart.min.js"></script>
    <script src="assets/vendors/chartjs/roundedBar.min.js"></script>

    <script src="assets/vendors/progressbar/jquery.barfiller.js"></script>

    <script src="assets/vendors/tagsinput/tagsinput.js"></script>

    <script src="assets/vendors/text_editor/summernote-bs4.js"></script>
    <script src="assets/vendors/am_chart/amcharts.js"></script>

    <script src="assets/vendors/scroll/perfect-scrollbar.min.js"></script>
    <script src="assets/vendors/scroll/scrollable-custom.js"></script>

    <!-- <script src="assets/vendors/vectormap-home/vectormap-2.0.2.min.js"></script> -->
     <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/jquery.vectormap@2.0.2/jquery-jvectormap.css">

    <!-- <script src="assets/vendors/vectormap-home/vectormap-world-mill-en.js"></script> -->
    <script src="https://cdn.jsdelivr.net/npm/jquery.vectormap@2.0.2/maps/jquery-jvectormap-world-mill-en.js"></script>

    <!-- <script src="assets/vendors/apex_chart/apex-chart2.js"></script> -->
    <!-- <script src="assets/vendors/apex_chart/apex_dashboard.js"></script> -->
    <script src="assets/vendors/echart/echarts.min.js"></script>
    <script src="assets/vendors/chart_am/core.js"></script>
    <script src="assets/vendors/chart_am/charts.js"></script>
    <script src="assets/vendors/chart_am/animated.js"></script>
    <script src="assets/vendors/chart_am/kelly.js"></script>
    <script src="assets/vendors/chart_am/chart-custom.js"></script>

    <script src="assets/js/dashboard_init.js"></script>
    <script src="assets/js/custom.js"></script>
</body>
</html>
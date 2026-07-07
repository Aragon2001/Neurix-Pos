<?php (defined('BASEPATH')) OR exit('No direct script access allowed'); ?>
<script type="text/javascript">
    (function () {
        var TYPE_DOCUMENT = <?= (int) ($type_document ?? 1); ?>;

        function qzReady() {
            return window.qz && qz.websocket.isActive();
        }

        function printBytes(url) {
            return fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(function (r) { return r.json(); })
                .then(function (res) {
                    if (!res || res.status !== 1) {
                        throw new Error('print_bytes_failed');
                    }
                    if (!qzReady()) {
                        throw new Error('qz_not_connected');
                    }
                    var printerName = localStorage.getItem('nx-qz-printer');
                    if (!printerName) {
                        var modal = document.getElementById('printerConfigModal');
                        if (modal && window.bootstrap) {
                            window.bootstrap.Modal.getOrCreateInstance(modal).show();
                        }
                        throw new Error('no_printer_configured');
                    }
                    var config = qz.configs.create(printerName);
                    return qz.print(config, [{ type: 'raw', format: 'command', flavor: 'base64', data: res.bytes }]);
                });
        }

        window.printReceipt = function () {
            printBytes('<?= site_url('posprint/receipt_bytes'); ?>/<?= (int) $inv->id; ?>/' + TYPE_DOCUMENT)
                .catch(function () {
                    if (window.bootbox) { bootbox.alert('<?= lang('pos_print_error'); ?>'); }
                });
            return false;
        };

        window.openCashDrawer = function () {
            printBytes('<?= site_url('posprint/drawer_bytes'); ?>')
                .catch(function () {
                    if (window.bootbox) { bootbox.alert('<?= lang('pos_print_error'); ?>'); }
                });
            return false;
        };

        <?php if (!empty($Settings->auto_print) && empty($modal)): ?>
        document.addEventListener('DOMContentLoaded', function () {
            setTimeout(window.printReceipt, 1000);
        });
        <?php endif; ?>
    })();
</script>

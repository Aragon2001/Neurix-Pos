<?php
/**
 * @package   Neurix POS
 * @author    Jostin Aragón Barboza
 * @copyright Arasoft Solutions
 */
(defined('BASEPATH')) OR exit('No direct script access allowed'); ?>

<div class="nxt-head">
    <div class="nxt-title">
        <?= lang('users'); ?>
        <small><?= lang('list_results'); ?></small>
    </div>
    <div class="nxt-head-actions">
        <a class="nxt-btn" href="<?= site_url('auth/create_user'); ?>">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>
            <?= lang('create_user'); ?>
        </a>
    </div>
</div>

<div id="nxtList"></div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var USERS = <?php
        $rows = array();
        foreach ($users as $user) {
            $rows[] = array(
                'id' => $user->id,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'email' => $user->email,
                'group' => $user->group,
                'group_slug' => $user->group_slug,
                'store' => $user->store,
                'active' => (int) $user->active,
            );
        }
        echo json_encode($rows);
    ?>;
    var U = '<?= site_url(); ?>/';
    new NxTable({
        el: '#nxtList',
        data: USERS,
        minWidth: '820px',
        unit: '<?= lang('users'); ?>'.toLowerCase(),
        exportName: 'usuarios',
        search: ['first_name', 'last_name', 'email', 'group', 'store'],
        chips: { key: 'group', all: '<?= lang('todas'); ?>' },
        columns: [
            { key: 'first_name', label: '<?= lang('name'); ?>', sortable: 'str', render: function (r) {
                return NxTable.entity((r.first_name + ' ' + (r.last_name || '')).trim(), r.email, r.email);
            } },
            { key: 'group', label: '<?= lang('group'); ?>', render: function (r) {
                var tono = { admin: 'violet', supervisor: 'orange' }[r.group_slug] || 'info';
                return NxTable.badge(r.group, tono);
            } },
            { key: 'store', label: '<?= lang('store'); ?>', render: function (r) {
                return r.store ? '<span class="nxt-dim-mono">' + NxTable.esc(r.store) + '</span>' : '—';
            } },
            { key: 'active', label: '<?= lang('status'); ?>', render: function (r) {
                return r.active ? NxTable.badge('<?= lang('active'); ?>', 'ok') : NxTable.badge('<?= lang('inactive'); ?>', 'err');
            }, exportValue: function (r) { return r.active ? '<?= lang('active'); ?>' : '<?= lang('inactive'); ?>'; } },
            { key: 'Actions', label: '<?= lang('actions'); ?>', width: '100px', noExport: true, render: function (r) {
                return '<div class="nxt-actions">' +
                    '<a class="nxt-icon-btn warn" href="' + U + 'users/profile/' + r.id + '" title="<?= lang('profile'); ?>"><i class="fa fa-edit"></i></a>' +
                    '<a class="nxt-icon-btn danger" href="' + U + 'auth/delete/' + r.id + '" data-confirm="<?= lang('alert_x_user'); ?>" title="<?= lang('delete_user'); ?>"><i class="fa fa-trash-o"></i></a></div>';
            } }
        ],
        i18n: {
            searchPlaceholder: '<?= lang('buscar_ph'); ?>',
            empty: '<?= lang('sin_resultados'); ?>',
            showing: '<?= lang('mostrando'); ?>', of: '<?= lang('de'); ?>', all: '<?= lang('todas'); ?>'
        }
    });
});
</script>

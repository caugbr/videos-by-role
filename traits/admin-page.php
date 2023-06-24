<?php
trait admin_page {
    
    public function videos_admin_page() {
		add_options_page(
			__('Vimeo videos by role', 'vbr'),
			__('Videos by role', 'vbr'),
			'manage_options',
			'video-options',
			[$this, 'admin_page']
		);
    }
    
    private function save_admin_page() {
        $msg = '';
        if (count($_POST) > 0) {
            if ($_POST['act'] == 'add-role') {
                if (!empty($_POST['role'])) {
                    $this->add_role($_POST['role'], $_POST['capabilities']);
                }
                if (!empty($_POST['cat_name'])) {
                    $this->add_category($_POST['cat_name']);
                }
                $msg = __('User level successfully added', 'vbr');
            }
            if ($_POST['act'] == 'remove-role') {
                $this->remove_role($_POST['role']);
                $this->remove_category($_POST['role']);
                $this->remove_capability($_POST['role']);
                $msg = __('User level successfully removed', 'vbr');
            }
            if ($_POST['act'] == 'save-config') {
                if (!empty($_POST['providers'])) {
                    update_option('vbr_providers', $_POST['providers']);
                $msg = __('Used providers successfully saved', 'vbr');
                }
            }
        }
        return $msg;
    }
    
    public function admin_page() {
        $msg = $this->save_admin_page();
        $roles = $this->get_roles();
        $caps = $this->get_capabilities();
        $providers = $this->get_providers();
        ?>
        <script>
            var vbrRoles = <?php print json_encode($roles) ?>;
        </script>
        <div class="wrap">
            <?php if (!empty($msg)) { ?>
                <div class="message"><?php print $msg; ?></div>
            <?php } ?>
            <h1><?php _e('Vimeo videos by role', 'vbr') ?></h1>
            <p>
                <?php _e('Create roles and publish videos organized in categories and restricted by user roles.', 'vbr'); ?>
            </p>
            <h2><?php _e('User levels', 'vbr') ?></h2>
            <div class="user-levels">
                <?php if (count($roles) == 0) { ?>
                    <div class="message"><?php _e('No levels yet', 'vbr') ?></div>
                <?php } ?>
                <?php foreach ($roles as $slug => $role) { ?>
                    <div class="role">
                        <div class="actions">
                            <button type="button" class="remove-role" data-role="<?php print $slug; ?>">
                                <?php _e('Remove', 'vbr') ?>
                            </button>
                        </div>
                        <h4><?php print $role['name']; ?></h4>
                        <?php _e('Slug:', 'vbr') ?> <strong><?php print $slug; ?></strong><br>
                        <?php _e('Capabilities:', 'vbr') ?> <strong><?php print join(', ', $role['capabilities']); ?></strong>
                    </div>
                <?php } ?>
            </div>
            <h2><?php _e('Create user level', 'vbr') ?></h2>
            <form method="post" action="options-general.php?page=video-options">
                <table class="form-table">
                    <tbody>
                        <tr>
                            <th><?php _e('Level name', 'vbr') ?></th>
                            <td>
                                <input type="text" name="role" id="role" class="regular-text ltr">
                                <div class="existent-roles">
                                    <?php _e('Existent levels:', 'vbr') ?>
                                    <span class="role-names"></span>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <th><?php _e('Capabilities', 'vbr') ?></th>
                            <td>
                                <ul class="capabilities">
                                    <?php foreach ($caps as $cap) { ?>
                                        <li>
                                            <label>
                                                <input 
                                                    type="checkbox" 
                                                    name="capabilities[]" 
                                                    id="cap_<?php print $cap; ?>" 
                                                    value="<?php print $cap; ?>"
                                                /> 
                                                <span><?php print $cap; ?></span>
                                            </label>
                                        </li>
                                    <?php } ?>
                                </ul>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="2" class="buttons">
                                <input type="submit" id="submit" value="<?php _e('Create level', 'vbr') ?>" class="button button-primary button-large" disabled>
                            </td>
                        </tr>
                    </tbody>
                </table>
                <input type="hidden" name="cat_name" id="cat_name">
                <input type="hidden" name="cat_slug" id="cat_slug">
                <input type="hidden" value="add-role" name="act">
            </form>
            <h2><?php _e('Supported video providers', 'vbr') ?></h2>
            <form method="post" action="options-general.php?page=video-options">
                <table class="form-table">
                    <tbody>
                        <tr>
                            <th><?php _e('Current providers', 'vbr') ?></th>
                            <td>
                                <ul class="providers">
                                    <?php
                                        $providers_path = WP_PLUGIN_DIR . '/videos-by-role/oembed-providers';
                                        $provs = array_diff(scandir($providers_path), array('..', '.'));
                                        foreach ($provs as $prv) {
                                            $prov = str_replace('.js', '', $prv);
                                            $path = $providers_path . '/' . $prv;
                                            $js = file_get_contents($path);
                                            if (preg_match("/name: *[\"']([^\"']+)[\"']/", $js, $matches)) {
                                                ?>
                                                <li>
                                                    <label for="providers-<?php print $prov; ?>">
                                                        <input 
                                                        type="checkbox" 
                                                        name="providers[]" 
                                                        id="providers-<?php print $prov; ?>"
                                                        value="<?php print $prov; ?>"
                                                        <?php if (in_array($prov, $providers)) print 'checked'; ?>
                                                    >
                                                        <span><?php print $matches[1]; ?></span>
                                                    </label>
                                                </li>
                                                <?php
                                            }
                                        }
                                    ?>
                                </ul>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="2" class="buttons">
                                <input type="submit" id="submit" value="<?php _e('Save', 'vbr') ?>" class="button button-primary button-large">
                            </td>
                        </tr>
                    </tbody>
                </table>
                <input type="hidden" value="save-config" name="act">
            </form>
            <form method="post" id="role_form" action="options-general.php?page=video-options">
                <input type="hidden" id="role_act" name="act">
                <input type="hidden" id="role_slug" name="role">
            </form>
            <style>
                .wrap > div.message {
                    padding: 1rem;
                    color: #1e255c;
                    font-size: 18px;
                    background-color: #f3fff9;
                }
                input.error {
                    color: #FF0000 !important;
                    border-color: #FF0000 !important;
                    outline: 0px !important;
                }
                .user-levels .role {
                    position: relative;
                    background-color: #e1e1e1;
                    margin: 0 auto 1.2rem;
                    padding: 0.5rem;
                }
                .user-levels .role h4 {
                    display: block;
                    font-size: 18px;
                    margin: 0 0 0.25rem 0;
                }
                .user-levels .role .actions {
                    position: absolute;
                    top: 0.5rem;
                    right: 0.5rem;
                    display: none;
                }
                .user-levels .role:hover .actions {
                    display: inline-block;
                }
                ul.capabilities {
                    margin: 0;
                }
            </style>
        </div>
        <?php
    }
}
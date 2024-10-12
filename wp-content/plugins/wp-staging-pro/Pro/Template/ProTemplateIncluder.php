<?php

namespace WPStaging\Pro\Template;

use WPStaging\Core\Forms\Form;

class ProTemplateIncluder
{
    /** @var string */
    private $backEndViewsFolder;

    public function __construct()
    {
        $this->backEndViewsFolder = trailingslashit(WPSTG_PLUGIN_DIR) . 'Backend/Pro/views/';
    }

    /**
     * Add the "Push" button to the template
     *
     * @param string $cloneID
     * @param array $data
     * @param object $license
     * @return void
     */
    public function addPushButton(string $cloneID, array $data, $license)
    {
        include $this->backEndViewsFolder . 'clone/ajax/push-button.php';
    }

    /**
     * Add the "Edit this Clone" link to the template
     *
     * @param string $cloneID
     * @param array $data
     * @param object $license
     * @return void
     */
    public function addEditCloneLink(string $cloneID, array $data, $license)
    {
        include $this->backEndViewsFolder . 'clone/ajax/edit-clone.php';
    }

    /**
     * Add generate login link to the action menu for staging site
     *
     * @param string $cloneID
     * @param array $data
     * @param object $license
     * @return void
     */
    public function addGenerateLoginLink(string $cloneID, array $data, $license)
    {
        include $this->backEndViewsFolder . 'clone/ajax/generate-login.php';
    }

    /**
     * Add "Sync User Account" button on the actions tab
     *
     * @param string $cloneID
     * @param array $data
     * @return void
     */
    public function addSyncAccountButton(string $cloneID, array $data)
    {
        include $this->backEndViewsFolder . 'clone/ajax/sync-button.php';
    }

    /**
     * @param Form $form
     */
    public function addProSettings(Form $form)
    {
        include $this->backEndViewsFolder . 'settings/general.php';
    }
}

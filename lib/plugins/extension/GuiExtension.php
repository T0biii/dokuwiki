<?php

namespace dokuwiki\plugin\extension;

class GuiExtension extends Gui
{
    protected Extension $extension;

    public function __construct(Extension $extension)
    {
        $this->extension = $extension;
    }


    public function render()
    {

        $classes = $this->getClasses();

        $html = "<section class=\"$classes\">";
        $html .= $this->thumbnail();
        $html .= $this->info();
        $html .= $this->actions();

        $html .= '</section>';
    }

    /**
     * Create the classes representing the state of the extension
     *
     * @return string
     */
    protected function getClasses()
    {
        $classes = ['extension', $this->extension->getType()];
        if ($this->extension->isInstalled()) $classes[] = 'installed';
        if ($this->extension->isUpdateAvailable()) $classes[] = 'update';
        $classes[] = $this->extension->isEnabled() ? 'enabled' : 'disabled';
        return implode(' ', $classes);
    }

    protected function thumbnail()
    {

    }

    protected function info()
    {
        $html = '<h2>';
        $html .= '<strong><bdi>' . hsc($this->extension->getDisplayName()) . '</bdi></strong>';
        $html .= '</h2>';
    }

    protected function actions()
    {

    }


    /**
     * Generate the link to the plugin homepage
     *
     * @return string The HTML code
     */
    public function makeHomepageLink()
    {
        global $conf;
        $url = $this->extension->getURL();
        if (preg_match('/^https?:\/\/(www\.)?dokuwiki\.org\//i', $url)) {
            $linktype = 'interwiki';
        } else {
            $linktype = 'extern';
        }
        $param = [
            'href' => $url,
            'title' => $url,
            'class' => ($linktype == 'extern') ? 'urlextern' : 'interwiki iw_doku',
            'target' => $conf['target'][$linktype],
            'rel' => ($linktype == 'extern') ? 'noopener' : ''
        ];
        if ($linktype == 'extern' && $conf['relnofollow']) {
            $param['rel'] = implode(' ', [$param['rel'], 'ugc nofollow']);
        }
        $html = ' <a ' . buildAttributes($param, true) . '>' . $this->getLang('homepage_link') . '</a>';
        return $html;
    }

    /**
     * Generate a link to the author of the extension
     *
     * @return string The HTML code of the link
     */
    public function makeAuthor()
    {
        if ($this->extension->getAuthor()) {
            $mailid = $this->extension->getEmailID();
            if ($mailid) {
                $url = $this->tabURL('search', ['q' => 'authorid:' . $mailid]);
                $html = '<a href="' . $url . '" class="author" title="' . $this->getLang('author_hint') . '" >' .
                    '<img src="//www.gravatar.com/avatar/' . $mailid .
                    '?s=60&amp;d=mm" width="20" height="20" alt="" /> ' .
                    hsc($this->extension->getAuthor()) . '</a>';
            } else {
                $html = '<span class="author">' . hsc($this->extension->getAuthor()) . '</span>';
            }
            $html = '<bdi>' . $html . '</bdi>';
        } else {
            $html = '<em class="author">' . $this->getLang('unknown_author') . '</em>';
        }
        return $html;
    }

    /**
     * Get the link and image tag for the screenshot/thumbnail
     *
     * @return string The HTML code
     */
    public function makeScreenshot()
    {
        $screen = $this->extension->getScreenshotURL();
        $thumb = $this->extension->getThumbnailURL();

        if ($screen) {
            // use protocol independent URLs for images coming from us #595
            $screen = str_replace('http://www.dokuwiki.org', '//www.dokuwiki.org', $screen);
            $thumb = str_replace('http://www.dokuwiki.org', '//www.dokuwiki.org', $thumb);

            $title = sprintf($this->getLang('screenshot'), hsc($this->extension->getDisplayName()));
            $img = '<a href="' . hsc($screen) . '" target="_blank" class="extension_screenshot">' .
                '<img alt="' . $title . '" width="120" height="70" src="' . hsc($thumb) . '" />' .
                '</a>';
        } elseif ($this->extension->isTemplate()) {
            $img = '<img alt="" width="120" height="70" src="' . DOKU_BASE .
                'lib/plugins/extension/images/template.png" />';
        } else {
            $img = '<img alt="" width="120" height="70" src="' . DOKU_BASE .
                'lib/plugins/extension/images/plugin.png" />';
        }
        $html = '<div class="screenshot" >' . $img . '<span></span></div>' . DOKU_LF;
        return $html;
    }

    /**
     * Extension main description
     *
     * @return string The HTML code
     */
    public function makeLegend()
    {
        $html  = '<div>';
        $html .= '<h2>';
        $html .= sprintf(
            $this->getLang('extensionby'),
            '<bdi>' . hsc($this->extension->getDisplayName()) . '</bdi>',
            $this->makeAuthor()
        );
        $html .= '</h2>' . DOKU_LF;

        $html .= $this->makeScreenshot();

        $popularity = $this->extension->getPopularity();
        if ($popularity !== false && !$this->extension->isBundled()) {
            $popularityText = sprintf($this->getLang('popularity'), round($popularity * 100, 2));
            $html .= '<div class="popularity" title="' . $popularityText . '">' .
                '<div style="width: ' . ($popularity * 100) . '%;">' .
                '<span class="a11y">' . $popularityText . '</span>' .
                '</div></div>' . DOKU_LF;
        }

        if ($this->extension->getDescription()) {
            $html .= '<p><bdi>';
            $html .=  hsc($this->extension->getDescription()) . ' ';
            $html .= '</bdi></p>' . DOKU_LF;
        }

        $html .= $this->makeLinkbar();
        $html .= $this->makeInfo();
        $html .= $this->makeNoticeArea();
        $html .= '</div>' . DOKU_LF;
        return $html;
    }

    /**
     * Generate the link bar HTML code
     *
     * @return string The HTML code
     */
    public function makeLinkbar()
    {
        global $conf;
        $html  = '<div class="linkbar">';
        $html .= $this->makeHomepageLink();

        $bugtrackerURL = $this->extension->getBugtrackerURL();
        if ($bugtrackerURL) { // FIXME simplify, bugtrackers never point to dokuwiki.org
            if (strtolower(parse_url($bugtrackerURL, PHP_URL_HOST)) == 'www.dokuwiki.org') {
                $linktype = 'interwiki';
            } else {
                $linktype = 'extern';
            }
            $param = [
                'href'   => $bugtrackerURL,
                'title'  => $bugtrackerURL,
                'class'  => 'bugs',
                'target' => $conf['target'][$linktype],
                'rel'    => ($linktype == 'extern') ? 'noopener' : ''
            ];
            if ($conf['relnofollow']) {
                $param['rel'] = implode(' ', [$param['rel'], 'ugc nofollow']);
            }
            $html .= ' <a ' . buildAttributes($param, true) . '>' .
                $this->getLang('bugs_features') . '</a>';
        }

        if ($this->extension->getTags()) { // FIXME simplify with array map
            $first = true;
            $html .= ' <span class="tags">' . $this->getLang('tags') . ' ';
            foreach ($this->extension->getTags() as $tag) {
                if (!$first) {
                    $html .= ', ';
                } else {
                    $first = false;
                }
                $url = $this->tabURL('search', ['q' => 'tag:' . $tag]);
                $html .= '<bdi><a href="' . $url . '">' . hsc($tag) . '</a></bdi>';
            }
            $html .= '</span>';
        }
        $html .= '</div>' . DOKU_LF;
        return $html;
    }

    /**
     * Notice area
     *
     * @return string The HTML code
     */
    public function makeNoticeArea()
    {
        $html = '';

        /* FIXME do we still need this?
        $missing_dependencies = $this->extension->getMissingDependencies();
        if (!empty($missing_dependencies)) {
            $html .= '<div class="msg error">' .
                sprintf(
                    $this->getLang('missing_dependency'),
                    '<bdi>' . implode(', ', $missing_dependencies) . '</bdi>'
                ) .
                '</div>';
        }
        */

        if ($this->extension->isInWrongFolder()) {
            $html .= '<div class="msg error">' .
                sprintf(
                    $this->getLang('wrong_folder'),
                    '<bdi>' . hsc(basename($this->extension->getCurrentDir())) . '</bdi>',
                    '<bdi>' . hsc($this->extension->getBase()) . '</bdi>'
                ) .
                '</div>';
        }
        // FIXME simplify with a loop
        if (($securityissue = $this->extension->getSecurityIssue()) !== false) {
            $html .= '<div class="msg error">' .
                sprintf($this->getLang('security_issue'), '<bdi>' . hsc($securityissue) . '</bdi>') .
                '</div>';
        }
        if (($securitywarning = $this->extension->getSecurityWarning()) !== false) {
            $html .= '<div class="msg notify">' .
                sprintf($this->getLang('security_warning'), '<bdi>' . hsc($securitywarning) . '</bdi>') .
                '</div>';
        }
        if (($updateMessage = $this->extension->getUpdateMessage()) !== false) {
            $html .=  '<div class="msg notify">' .
                sprintf($this->getLang('update_message'), '<bdi>' . hsc($updateMessage) . '</bdi>') .
                '</div>';
        }
        if ($this->extension->hasChangedURL()) {
            $html .= '<div class="msg notify">' .
                sprintf(
                    $this->getLang('url_change'),
                    '<bdi>' . hsc($this->extension->getDownloadURL()) . '</bdi>',
                    '<bdi>' . hsc($this->extension->getManager()->getDownloadURL()) . '</bdi>'
                ) .
                '</div>';
        }

        if ($this->extension->isUpdateAvailable()) {
            $html .=  '<div class="msg notify">' .
                sprintf($this->getLang('update_available'), hsc($this->extension->getLastUpdate())) .
                '</div>';
        }

        return $html . DOKU_LF;
    }

    /**
     * Plugin/template details
     *
     * @return string The HTML code
     */
    public function makeInfo()
    {
        $default = $this->getLang('unknown');


        $list = [];

        $list['status'] = $this->makeStatus();


        if ($this->extension->getDonationURL()) {
            $list['donate'] = '<a href="' . $this->extension->getDonationURL() . '" class="donate">' .
                $this->getLang('donate_action') . '</a>';
        }

        if (!$this->extension->isBundled()) {
            $list['downloadurl'] = $this->shortlink($this->extension->getDownloadURL(), $default);
            $list['repository'] = $this->shortlink($this->extension->getSourcerepoURL(), $default);
        }

        if ($this->extension->isInstalled()) {
            if ($this->extension->getInstalledVersion()) {
                $list['installed_version'] = hsc($this->extension->getInstalledVersion());
            }
            if (!$this->extension->isBundled()) {
                $updateDate = $this->extension->getManager()->getLastUpdate();
                $list['install_date'] = $updateDate ? hsc($updateDate) : $default;
            }
        }

        if (!$this->extension->isInstalled() || $this->extension->isUpdateAvailable()) {
            $list['available_version'] = $this->extension->getLastUpdate()
                ? hsc($this->extension->getLastUpdate())
                : $default;
        }


        if (!$this->extension->isBundled() && $this->extension->getCompatibleVersions()) {
            $html .= '<dt>' . $this->getLang('compatible') . '</dt>';
            $html .= '<dd>';
            foreach ($this->extension->getCompatibleVersions() as $date => $version) {
                $html .= '<bdi>' . $version['label'] . ' (' . $date . ')</bdi>, ';
            }
            $html = rtrim($html, ', ');
            $html .= '</dd>';
        }
        if ($this->extension->getDependencyList()) {
            $html .= '<dt>' . $this->getLang('depends') . '</dt>';
            $html .= '<dd>';
            $html .= $this->makeLinkList($extension->getDependencies());
            $html .= '</dd>';
        }

        if ($this->extension->getSimilarExtensions()) {
            $html .= '<dt>' . $this->getLang('similar') . '</dt>';
            $html .= '<dd>';
            $html .= $this->makeLinkList($extension->getSimilarExtensions());
            $html .= '</dd>';
        }

        if ($this->extension->getConflicts()) {
            $html .= '<dt>' . $this->getLang('conflicts') . '</dt>';
            $html .= '<dd>';
            $html .= $this->makeLinkList($extension->getConflicts());
            $html .= '</dd>';
        }
        $html .= '</dl>' . DOKU_LF;
        return $html;
    }


    /**
     * Create a link from the given URL
     *
     * Shortens the URL for display
     *
     * @param string $url
     * @param string $fallback If URL is empty return this fallback
     * @return string  HTML link
     */
    public function shortlink($url, $fallback = '')
    {
        if(!$url) return hsc($fallback);

        $link = parse_url($url);
        $base = $link['host'];
        if (!empty($link['port'])) $base .= $base . ':' . $link['port'];
        $long = $link['path'];
        if (!empty($link['query'])) $long .= $link['query'];

        $name = shorten($base, $long, 55);

        $html = '<a href="' . hsc($url) . '" class="urlextern">' . hsc($name) . '</a>';
        return $html;
    }
}

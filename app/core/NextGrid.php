<?php

include('simplehtmldom/simple_html_dom.php');

/**
 * Gets the content from CodePen,
 * parses it with the help of "PHP Simple HTML DOM Parser" (http://sourceforge.net/projects/simplehtmldom/)
 * and returns the output as an array.
 *
 *
 * 2012 by timpietrusky.com
 *
 * Licensed under VVL 1.33b7 - timpietrusky.com/license
 */
class NextGrid {

    protected $output = array();
    protected $html;

    const VALUE_TYPE_ATTRIBUTE = 'attribute',
          VALUE_TYPE_PLAINTEXT = 'plaintext';

    function __construct() {
        $A = $this->getA();
        $page = $this->getPage();
        $url = "";

        // user
        if (Master::$Request->getA() == Config::getConfig()->type_user) {
            $user_destination = $this->getUserDestination();
            $url = Config::getConfig()->codepen . "/$A/next_grid?type=$user_destination&page=$page&size=small";
        // home
        } else {
            $type = Master::$Request->getResourcePart(1);
            $url = Config::getConfig()->codepen . "/$A/next_grid?type=$type&page=$page&size=large";
        }

        // Get JSON from CodePen
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        $content_json = curl_exec($ch);
        curl_close($ch);

        // Decode the delivered JSON
        $output = json_decode($content_json, true);

        // Create a DOM object from a string
        $this->html = str_get_html($output['html']);
    }

    /**
     * Creates the output.
     *
     * @return Array $output
     */
    public function getOutput() {
        // Get all pens
        $pens = $this->html->find('div[class="single-pen group"]');
        $pens_count = count($pens);

        // Has pens
        if ($pens_count > 0) {
            // Extract the info of every single pen
            for ($i = 0; $i < $pens_count; $i++) {
                /*
                 * user
                 */
                // Title
                $this->output['pens'][$i]['title'] = $this->getValue($pens[$i], 'div[class="meta-overlay"] h2', NextGrid::VALUE_TYPE_PLAINTEXT);

                // Description
                $this->output['pens'][$i]['description'] = $this->getValue($pens[$i], 'div[class="meta-overlay] p', NextGrid::VALUE_TYPE_PLAINTEXT);

                // Views
                $this->output['pens'][$i]['views'] = $this->getValue($pens[$i], 'a[class="views"]', NextGrid::VALUE_TYPE_PLAINTEXT);

                // Hearts
                $this->output['pens'][$i]['hearts'] = $this->getValue($pens[$i], 'span[class="count"]', NextGrid::VALUE_TYPE_PLAINTEXT);

                // URL - pen
                $this->output['pens'][$i]['url']['pen'] = $this->getValue($pens[$i], 'a[class="cover-link"]', NextGrid::VALUE_TYPE_ATTRIBUTE, 'href');

                // URL - fullgrid
                $this->output['pens'][$i]['url']['fullgrid'] = $this->getValue($pens[$i], 'iframe[data-src]', NextGrid::VALUE_TYPE_ATTRIBUTE, 'data-src');

                /*
                 * home/* & user/love
                 */
                if (Master::$Request->getA() == Config::getConfig()->type_home ||
                    Master::$Request->getB() == 'love') {

                    // User - name
                    $this->output['pens'][$i]['user']['nickname'] = substr($this->getValue($pens[$i], 'div[class="user"] a', NextGrid::VALUE_TYPE_ATTRIBUTE, 'href'), 1);

                    // User - realname
                    $this->output['pens'][$i]['user']['realname'] = $this->getValue($pens[$i], 'div[class="user"] a', NextGrid::VALUE_TYPE_PLAINTEXT);

                    // User - gravatar
                    $this->output['pens'][$i]['user']['gravatar'] = $this->getValue($pens[$i], 'div[class="user"] img', NextGrid::VALUE_TYPE_ATTRIBUTE, 'src');

                    // No gravatar set
                    if ($this->output['pens'][$i]['user']['gravatar'] == '/images/avatars/no-avatar.png') {
                        $this->output['pens'][$i]['user']['gravatar'] = Config::getConfig()->default_value_null;
                    }
                }
            }
        }

        return $this->output;
    }

    public function getA() {
        // user
        if (Master::$Request->getA() == Config::getConfig()->type_user) {
            return Master::$Request->getResourcePart(1);
        // home
        } else {
            return Master::$Request->getA();
        }
    }

    public function getUserDestination() {
        return Master::$Request->getResourcePart(2);
    }

    public function getPage() {
        // user
        if (Master::$Request->getA() == Config::getConfig()->type_user) {
            return Master::$Request->getResourcePart(3);
        // home
        } else {
            return Master::$Request->getResourcePart(2);
        }
    }

    /**
     * Returns the value of the found element <CODE>$toFind</CODE>
     * with the type <CODE>$type</CODE>.
     *
     * If <CODE>$type</CODE> is <CODE>NextGrid::VALUE_TYPE_ATTRIBUTE</CODE>,
     * then the attribute-name <CODE>$attribute</CODE> must be specified.
     *
     * @param simple_html_dom_node $pen
     * @param String $toFind
     * @param String $type
     * @param String $attribute
     *
     * @return mixed $value
     */
    protected function getValue($pen, $toFind, $type, $attribute = "") {
        $value = $pen->find($toFind);

        // Value is not null
        if (isset($value[0])) {
            if ($type == NextGrid::VALUE_TYPE_ATTRIBUTE) {
                $value = $value[0]->getAttribute($attribute);
            }

            if ($type == NextGrid::VALUE_TYPE_PLAINTEXT) {
                $value = $value[0]->plaintext;
            }

            $value = trim($value);

            // Convert numbers
            if (is_numeric($value)) {
                $value = intval($value);
            }
        // Value is null / not set / not available
        } else {
            $value = Config::getConfig()->default_value_null;
        }

        return $value;
    }
}
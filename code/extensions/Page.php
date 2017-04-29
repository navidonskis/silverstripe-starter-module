<?php

namespace Extensions;

use Controller;
use Convert;
use DataExtension;
use DBField;
use FieldList;
use DataObject;
use HtmlEditorField;
use SiteTreeURLSegmentField;
use TextareaField;
use TextField;
use UploadField;
use URLSegmentFilter;

/**
 * @author      Donatas Navidonskis <donatas@navidonskis.com>
 * @since       2017
 * @class       Page
 * @package     Extensions
 * @description Extension to make DataObject as Page (SiteTree).
 *
 * @property int        MetaPictureID
 * @property string     Title
 * @property string     MenuTitle
 * @property string     URLSegment
 * @property string     Content
 * @property string     MetaDescription
 * @property string     MetaKeywords
 * @property DataObject owner
 *
 * @method Image MetaPicture
 * @method DataObject getOwner
 */
class Page extends DataExtension {

    /**
     * @var array
     * @config
     */
    private static $db = [
        'Title'           => 'Varchar(255)',
        'MenuTitle'       => 'Varchar(255)',
        'URLSegment'      => 'Varchar(318)',
        'Content'         => 'HTMLText',
        'MetaDescription' => 'Text',
        'MetaKeywords'    => 'Varchar',
        //        'SortOrder'       => 'Int',
    ];

    /**
     * @var array
     * @config
     */
    private static $indexes = [
        'URLSegment'   => true,
        'SearchFields' => [
            'type'  => 'fulltext',
            'name'  => 'SearchFields',
            'value' => '"Title", "Content", "MenuTitle", "MetaDescription", "MetaKeywords"',
        ],
    ];

    /**
     * @var array
     * @config
     */
    private static $create_table_options = [
        'MySQLDatabase' => 'ENGINE=MyISAM',
    ];

    /**
     * @var array
     * @config
     */
    private static $has_one = [
        'MetaPicture' => 'Image',
    ];

    /**
     * @var array
     * @config
     */
    private static $field_labels = [
        'URLSegment' => 'URL',
    ];

    /**
     * @param FieldList $fields
     *
     * @return void
     */
    public function updateCMSFields(FieldList $fields) {
        $fields->findOrMakeTab('Root.SEO', $this->getOwner()->fieldLabel('SEO'));

        $fields->addFieldsToTab('Root.Main', [
            $title = TextField::create('Title', $this->getOwner()->fieldLabel('Title')),
            $urlSegment = SiteTreeURLSegmentField::create('URLSegment', $this->getOwner()->fieldLabel('URLSegment')),
            $menuTitle = TextField::create('MenuTitle', $this->getOwner()->fieldLabel('MenuTitle')),
            $content = HTMLEditorField::create('Content', $this->getOwner()->fieldLabel('Content')),
        ]);

        $fields->addFieldsToTab('Root.SEO', [
            $description = TextareaField::create('MetaDescription', $this->fieldLabel('MetaDescription')),
            $keywords = TextField::create('MetaKeywords', $this->fieldLabel('MetaKeywords')),
            $picture = UploadField::create('MetaPicture', $this->fieldLabel('MetaPicture')),
        ]);

        $urlSegment
            ->setURLPrefix($this->getURLPrefix())
            ->setDefaultURL($this->generateURLSegment(_t(
                'CMSMain.NEWPAGE',
                'New {pagetype}',
                ['pagetype' => $this->getOwner()->i18n_singular_name()]
            )));

        $picture
            ->setAllowedFileCategories('image')
            ->setAllowedMaxFileNumber(1);

        $content->setRows(25);

        parent::updateCMSFields($fields);
    }

    /**
     * Override within your prefix of URLSegment. Example page holder link.
     *
     * @return string
     */
    public function getURLPrefix() {
        return '/';
    }

    /**
     * Absolute URL address
     *
     * @return string
     */
    public function Link() {
        return Controller::join_links(
            $this->getURLPrefix(),
            $this->getOwner()->URLSegment,
            '/'
        );
    }

    /**
     * @param string $title
     *
     * @return string
     */
    public function generateURLSegment($title) {
        $filter = URLSegmentFilter::create();
        $t = $filter->filter($title);

        // Fallback to generic page name if path is empty (= no valid, convertable characters)
        if (! $t || $t == '-' || $t == '-1') {
            $t = "page-$this->ID";
        }

        // Hook for extensions
        $this->getowner()->extend('updateURLSegment', $t, $title);

        return $t;
    }

    public function updateFieldLabels(&$labels) {
        $labels = array_merge($labels, [
            'Title'           => _t('SiteTree.PAGETITLE', "Page name"),
            'MenuTitle'       => _t('SiteTree.MENUTITLE', "Navigation label"),
            'URLSegment'      => _t('SiteTree.URLSegment', 'URL Segment', 'URL for this page'),
            'Content'         => _t('SiteTree.Content', 'Content', 'Main HTML Content for a page'),
            'SEO'             => _t('PageExtension.SEO', 'SEO'),
            'MetaDescription' => _t('SiteTree.METADESC', "Meta Description"),
            'MetaKeywords'    => _t('PageExtension.META_KEYWORDS', "Meta Keywords"),
            'MetaPicture'     => _t('PageExtension.META_PICTURE', "Meta Picture"),
        ]);

        parent::updateFieldLabels($labels);
    }

    /**
     * @param string $segment
     *
     * @return static|null
     */
    public static function getByUrlSegment($segment) {
        return static::get()->filter('URLSegment', $segment)->first();
    }

    /**
     * @param int $limit
     *
     * @return string
     */
    public function getShortDescription($limit = 20) {
        if (! empty($description = $this->getOwner()->MetaDescription)) {
            return $description;
        } elseif (! empty($content = $this->getOwner()->Content)) {
            $content = Convert::raw2att($content);
            /** @var Varchar $content */
            $content = DBField::create_field('Varchar', $content);
            $content = $content->LimitWordCount($limit);
            $content = strip_tags(preg_replace('/<[^>]*>/', '', str_replace(["&nbsp;", "\n", "\r"], "", html_entity_decode($content, ENT_QUOTES, 'UTF-8'))));

            return $content;
        }

        return false;
    }

}
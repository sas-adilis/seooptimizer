<?php

namespace Adilis\SeoOptimizer\Form;

use Adilis\SeoOptimizer\Utils;

class FormSitemap extends FormAbstract implements FormInterface
{
    /**
     * @throws \PrestaShopDatabaseException
     */
    public function getContent(): string
    {
        return $this->renderForm([
            'form' => [
                'legend' => [
                    'title' => $this->l('Sitemap configuration'),
                    'icon' => 'icon-sitemap',
                    'visual' => __PS_BASE_URI__ . 'modules/seooptimizer/views/img/panda-sitemap.png',
                ],
                'desc' => $this->l('The sitemap is a file where you can list the web pages of your site to tell Google and other search engines about the organization of your site content. Search engine web crawlers like Googlebot read this file to more intelligently crawl your site.'),
                'input' => [
                    [
                        'type' => 'switch',
                        'name' => 'SEOO_ENABLE_SITEMAP',
                        'required' => true,
                        'is_bool' => true,
                        'label' => $this->l('Enable sitemap'),
                        'values' => [
                            ['id' => 'SEOO_ENABLE_SITEMAP_on', 'value' => 1, 'label' => $this->l('Yes')],
                            ['id' => 'SEOO_ENABLE_SITEMAP_off', 'value' => 0, 'label' => $this->l('No')],
                        ],
                    ],
                    [
                        'type' => 'html',
                        'name' => 'name',
                        'form_group_class' => 'show-if-enable-sitemap form-group-html',
                        'label' => $this->l('Your sitemap URL'),
                        'desc' => $this->l('Give this URL to Google and other search engines to help them find your sitemap.'),
                        'html_content' => sprintf(
                            '<a href="%1$s" target="_blank">%1$s</a>',
                            \Context::getContext()->link->getPageLink('module-seooptimizer-sitemap-index')
                        ),
                        'lang' => true,
                    ],

                    [
                        'type' => 'priorities_frequencies',
                        'name' => 'priorities_frequencies',
                        'form_group_class' => 'show-if-enable-sitemap form-group-priorities-frequencies',
                        'required' => true,
                        'label' => $this->l('Define priorities and frequencies'),
                        'options' => [
                            'product' => [
                                'label' => $this->l('Product'),
                                'name' => 'SEOO_SITEMAP_PRODUCT',
                                'priority' => Utils::getValOrConf('SEOO_SITEMAP_PRODUCT_PRIORITY'),
                                'frequency' => Utils::getValOrConf('SEOO_SITEMAP_PRODUCT_FREQUENCY'),
                            ],
                            'category' => [
                                'label' => $this->l('Categories'),
                                'name' => 'SEOO_SITEMAP_CATEGORY',
                                'priority' => Utils::getValOrConf('SEOO_SITEMAP_CATEGORY_PRIORITY'),
                                'frequency' => Utils::getValOrConf('SEOO_SITEMAP_CATEGORY_FREQUENCY'),
                            ],
                            'manufacturer' => [
                                'label' => $this->l('Manufacturers'),
                                'name' => 'SEOO_SITEMAP_MANUFACTURER',
                                'priority' => Utils::getValOrConf('SEOO_SITEMAP_MANUFACTURER_PRIORITY'),
                                'frequency' => Utils::getValOrConf('SEOO_SITEMAP_MANUFACTURER_FREQUENCY'),
                            ],
                            'supplier' => [
                                'label' => $this->l('Suppliers'),
                                'name' => 'SEOO_SITEMAP_SUPPLIER',
                                'priority' => Utils::getValOrConf('SEOO_SITEMAP_SUPPLIER_PRIORITY'),
                                'frequency' => Utils::getValOrConf('SEOO_SITEMAP_SUPPLIER_FREQUENCY'),
                            ],
                            'cms' => [
                                'label' => $this->l('CMS'),
                                'name' => 'SEOO_SITEMAP_CMS',
                                'priority' => Utils::getValOrConf('SEOO_SITEMAP_CMS_PRIORITY'),
                                'frequency' => Utils::getValOrConf('SEOO_SITEMAP_CMS_FREQUENCY'),
                            ],
                            'cms_category' => [
                                'label' => $this->l('Categories CMS'),
                                'name' => 'SEOO_SITEMAP_CMS_CATEGORY',
                                'priority' => Utils::getValOrConf('SEOO_SITEMAP_CMS_CATEGORY_PRIORITY'),
                                'frequency' => Utils::getValOrConf('SEOO_SITEMAP_CMS_CATEGORY_FREQUENCY'),
                            ],
                            'other' => [
                                'label' => $this->l('Other pages'),
                                'name' => 'SEOO_SITEMAP_OTHER',
                                'priority' => Utils::getValOrConf('SEOO_SITEMAP_OTHER_PRIORITY'),
                                'frequency' => Utils::getValOrConf('SEOO_SITEMAP_OTHER_FREQUENCY'),
                            ],
                        ],
                    ],
                    [
                        'type' => 'switch',
                        'name' => 'SEOO_SITEMAP_ENABLE_PRODUCT_IMAGES',
                        'required' => true,
                        'is_bool' => true,
                        'form_group_class' => 'show-if-enable-sitemap show-if-sitemap-images',
                        'label' => $this->l('Enable products images in sitemap'),
                        'values' => [
                            ['id' => 'SEOO_SITEMAP_ENABLE_PRODUCT_IMAGES_on', 'value' => 1, 'label' => $this->l('Yes')],
                            ['id' => 'SEOO_SITEMAP_ENABLE_PRODUCT_IMAGES_off', 'value' => 0, 'label' => $this->l('No')],
                        ]
                    ],
                    [
                        'type' => 'select',
                        'name' => 'SEOO_SITEMAP_PRODUCT_IMAGE_FORMAT',
                        'id' => 'SEOO_SITEMAP_PRODUCT_IMAGE_FORMAT',
                        'label' => $this->l('Product image format'),
                        'desc' => $this->l('Select the image format to use for products images.'),
                        'required' => true,
                        'form_group_class' => 'show-if-product-images show-if-enable-sitemap',
                        'options' => [
                            'default' => [
                                'value' => null,
                                'label' => $this->l('Choose an image format'),
                            ],
                            'query' => \ImageType::getImagesTypes('products'),
                            'id' => 'id_image_type',
                            'name' => 'name',
                        ]
                    ],
                    [
                        'type' => 'switch',
                        'name' => 'SEOO_SITEMAP_ENABLE_CATEGORY_IMAGES',
                        'required' => true,
                        'is_bool' => true,
                        'form_group_class' => 'show-if-enable-sitemap',
                        'label' => $this->l('Enable categories images in sitemap'),
                        'values' => [
                            ['id' => 'SEOO_SITEMAP_ENABLE_CATEGORY_IMAGES_on', 'value' => 1, 'label' => $this->l('Yes')],
                            ['id' => 'SEOO_SITEMAP_ENABLE_CATEGORY_IMAGES_off', 'value' => 0, 'label' => $this->l('No')],
                        ]
                    ],
                    [
                        'type' => 'select',
                        'name' => 'SEOO_SITEMAP_CATEGORY_IMAGE_FORMAT',
                        'id' => 'SEOO_SITEMAP_CATEGORY_IMAGE_FORMAT',
                        'label' => $this->l('Category image format'),
                        'desc' => $this->l('Select the image format to use for categories images.'),
                        'required' => true,
                        'form_group_class' => 'show-if-category-images show-if-enable-sitemap',
                        'options' => [
                            'default' => [
                                'value' => null,
                                'label' => $this->l('Choose an image format'),
                            ],
                            'query' => \ImageType::getImagesTypes('categories'),
                            'id' => 'id_image_type',
                            'name' => 'name',
                        ]
                    ],
                    [
                        'type' => 'switch',
                        'name' => 'SEOO_SITEMAP_ENABLE_MANUFACTURER_IMAGES',
                        'required' => true,
                        'is_bool' => true,
                        'form_group_class' => 'show-if-enable-sitemap',
                        'label' => $this->l('Enable manufacturers images in sitemap'),
                        'values' => [
                            ['id' => 'SEOO_SITEMAP_ENABLE_MANUFACTURER_IMAGES_IMAGES_on', 'value' => 1, 'label' => $this->l('Yes')],
                            ['id' => 'SEOO_SITEMAP_ENABLE_MANUFACTURER_IMAGES_IMAGES_off', 'value' => 0, 'label' => $this->l('No')],
                        ]
                    ],
                    [
                        'type' => 'select',
                        'name' => 'SEOO_SITEMAP_MANUFACTURER_IMAGE_FORMAT',
                        'id' => 'SEOO_SITEMAP_MANUFACTURER_IMAGE_FORMAT',
                        'label' => $this->l('Manufacturer image format'),
                        'desc' => $this->l('Select the image format to use for manufacturers images.'),
                        'required' => true,
                        'form_group_class' => 'show-if-manufacturer-images show-if-enable-sitemap',
                        'options' => [
                            'default' => [
                                'value' => null,
                                'label' => $this->l('Choose an image format'),
                            ],
                            'query' => \ImageType::getImagesTypes('manufacturers'),
                            'id' => 'id_image_type',
                            'name' => 'name',
                        ]
                    ],
                    [
                        'type' => 'switch',
                        'name' => 'SEOO_SITEMAP_ENABLE_SUPPLIER_IMAGES',
                        'required' => true,
                        'is_bool' => true,
                        'form_group_class' => 'show-if-enable-sitemap',
                        'label' => $this->l('Enable suppliers images in sitemap'),
                        'values' => [
                            ['id' => 'SEOO_SITEMAP_ENABLE_SUPPLIER_IMAGES_on', 'value' => 1, 'label' => $this->l('Yes')],
                            ['id' => 'SEOO_SITEMAP_ENABLE_SUPPLIER_IMAGES_off', 'value' => 0, 'label' => $this->l('No')],
                        ]
                    ],
                    [
                        'type' => 'select',
                        'name' => 'SEOO_SITEMAP_SUPPLIER_IMAGE_FORMAT',
                        'id' => 'SEOO_SITEMAP_SUPPLIER_IMAGE_FORMAT',
                        'form_group_class' => 'show-if-supplier-images show-if-enable-sitemap',
                        'label' => $this->l('Supplier image format'),
                        'desc' => $this->l('Select the image format to use for suppliers images.'),
                        'required' => true,
                        'options' => [
                            'default' => [
                                'value' => null,
                                'label' => $this->l('Choose an image format'),
                            ],
                            'query' => \ImageType::getImagesTypes('suppliers'),
                            'id' => 'id_image_type',
                            'name' => 'name',
                        ]
                    ],
                    [
                        'type' => 'text',
                        'name' => 'SEOO_SITEMAP_PER_PAGE',
                        'label' => $this->l('Paginate sitemap by'),
                        'form_group_class' => 'show-if-enable-sitemap',
                        'desc' => $this->l('Number of links per sitemap page. Set to 0 to disable pagination.'),
                        'required' => true,
                        'class' => 'fixed-width-sm',
                    ],

                ],
                'submit' => [
                    'title' => $this->l('Save'),
                    'class' => 'btn btn-default pull-right',
                    'name' => 'submit' . $this->getKey(),
                ],
            ],
        ], [
            'priorities_frequencies' => '',
            'SEOO_ENABLE_SITEMAP' => Utils::getValOrConf('SEOO_ENABLE_SITEMAP'),
            'SEOO_SITEMAP_ENABLE_PRODUCT_IMAGES' => Utils::getValOrConf('SEOO_SITEMAP_ENABLE_PRODUCT_IMAGES'),
            'SEOO_SITEMAP_ENABLE_CATEGORY_IMAGES' => Utils::getValOrConf('SEOO_SITEMAP_ENABLE_CATEGORY_IMAGES'),
            'SEOO_SITEMAP_ENABLE_MANUFACTURER_IMAGES' => Utils::getValOrConf('SEOO_SITEMAP_ENABLE_MANUFACTURER_IMAGES'),
            'SEOO_SITEMAP_ENABLE_SUPPLIER_IMAGES' => Utils::getValOrConf('SEOO_SITEMAP_ENABLE_SUPPLIER_IMAGES'),
            'SEOO_SITEMAP_PER_PAGE' => Utils::getValOrConf('SEOO_SITEMAP_PER_PAGE'),
            'SEOO_SITEMAP_PRODUCT_IMAGE_FORMAT' => Utils::getValOrConf('SEOO_SITEMAP_PRODUCT_IMAGE_FORMAT'),
            'SEOO_SITEMAP_CATEGORY_IMAGE_FORMAT' => Utils::getValOrConf('SEOO_SITEMAP_CATEGORY_IMAGE_FORMAT'),
            'SEOO_SITEMAP_MANUFACTURER_IMAGE_FORMAT' => Utils::getValOrConf('SEOO_SITEMAP_MANUFACTURER_IMAGE_FORMAT'),
            'SEOO_SITEMAP_SUPPLIER_IMAGE_FORMAT' => Utils::getValOrConf('SEOO_SITEMAP_SUPPLIER_IMAGE_FORMAT'),
        ]);
    }

    public function postProcess()
    {
        Utils::saveFormIntConfiguration('SEOO_ENABLE_SITEMAP');
        Utils::saveFormIntConfiguration('SEOO_SITEMAP_ENABLE_PRODUCT_IMAGES');
        Utils::saveFormIntConfiguration('SEOO_SITEMAP_ENABLE_CATEGORY_IMAGES');
        Utils::saveFormIntConfiguration('SEOO_SITEMAP_ENABLE_MANUFACTURER_IMAGES');
        Utils::saveFormIntConfiguration('SEOO_SITEMAP_ENABLE_SUPPLIER_IMAGES');
        Utils::saveFormIntConfiguration('SEOO_SITEMAP_PER_PAGE');
        Utils::saveFormConfiguration('SEOO_SITEMAP_PRODUCT_PRIORITY');
        Utils::saveFormConfiguration('SEOO_SITEMAP_PRODUCT_FREQUENCY');
        Utils::saveFormConfiguration('SEOO_SITEMAP_CATEGORY_PRIORITY');
        Utils::saveFormConfiguration('SEOO_SITEMAP_CATEGORY_FREQUENCY');
        Utils::saveFormConfiguration('SEOO_SITEMAP_MANUFACTURER_PRIORITY');
        Utils::saveFormConfiguration('SEOO_SITEMAP_MANUFACTURER_FREQUENCY');
        Utils::saveFormConfiguration('SEOO_SITEMAP_SUPPLIER_PRIORITY');
        Utils::saveFormConfiguration('SEOO_SITEMAP_SUPPLIER_FREQUENCY');
        Utils::saveFormConfiguration('SEOO_SITEMAP_CMS_PRIORITY');
        Utils::saveFormConfiguration('SEOO_SITEMAP_CMS_FREQUENCY');
        Utils::saveFormConfiguration('SEOO_SITEMAP_CMS_CATEGORY_PRIORITY');
        Utils::saveFormConfiguration('SEOO_SITEMAP_CMS_CATEGORY_FREQUENCY');
        Utils::saveFormConfiguration('SEOO_SITEMAP_OTHER_PRIORITY');
        Utils::saveFormConfiguration('SEOO_SITEMAP_OTHER_FREQUENCY');
        Utils::saveFormConfiguration('SEOO_SITEMAP_PRODUCT_IMAGE_FORMAT');
        Utils::saveFormConfiguration('SEOO_SITEMAP_CATEGORY_IMAGE_FORMAT');
        Utils::saveFormConfiguration('SEOO_SITEMAP_MANUFACTURER_IMAGE_FORMAT');
        Utils::saveFormConfiguration('SEOO_SITEMAP_SUPPLIER_IMAGE_FORMAT');
        \Tools::redirectAdmin(Utils::getConfigFormUrl(4));
    }
}

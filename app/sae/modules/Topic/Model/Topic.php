<?php

class Topic_Model_Topic extends Core_Model_Default {

    protected $_is_cachable = false;

    public function __construct($params = array()) {
        parent::__construct($params);
        $this->_db_table = 'Topic_Model_Db_Table_Topic';
        return $this;
    }

    public function prepareFeature($option_value) {

        parent::prepareFeature($option_value);

        if(!$this->getId()) {
            $this->setValueId($option_value->getValueId())->setAppId($option_value->getAppId())->save();
        }

        return $this;
    }

    public function getCategories() {

        if(!$this->getId()) {
            $categories = array();
        } else {
            $category = new Topic_Model_Category();
            $categories = $category->getTopicCategories($this->getId());
        }

        return $categories;
    }

    public function copyTo($option) {
        $this->unsTopicId()
            ->unsId()
            ->setValueId($option->getId())
            ->setAppId($option->getAppId())
            ->save()
        ;

        $categories = $option->getObject()->getCategories();
        foreach($categories as $category) {
            $category->unsCategoryId()
                ->unsId()
                ->setTopicId($this->getId())
                ->save()
            ;
        }

        return $this;
    }

    /**
     * @param $option Application_Model_Option_Value
     * @return string
     * @throws Exception
     */
    public function exportAction($option, $export_type = null) {
        if($option && $option->getId()) {

            $current_option = $option;
            $value_id = $current_option->getId();

            $topic_model = new Topic_Model_Topic();
            $topic = $topic_model->find($value_id, "value_id");

            $topic_categories = array();
            if($topic->getId()) {
                $topic_category_model = new Topic_Model_Category();
                $tcs = $topic_category_model->findAll(array(
                    "topic_id = ?" => $topic->getId(),
                ));

                foreach($tcs as $tc) {
                    $topic_categories[] = $tc->getData();
                }
            }

            $dataset = array(
                "option" => $current_option->forYaml(),
                "topic" => $topic->getData(),
                "topic_categories" => $topic_categories,
            );

            try {
                $result = Siberian_Yaml::encode($dataset);
            } catch(Exception $e) {
                throw new Exception("#089-03: An error occured while exporting dataset to YAML.");
            }

            return $result;

        } else {
            throw new Exception("#089-01: Unable to export the feature, non-existing id.");
        }
    }

    /**
     * @param $path
     * @throws Exception
     */
    public function importAction($path) {
        $content = file_get_contents($path);

        try {
            $dataset = Siberian_Yaml::decode($content);
        } catch(Exception $e) {
            throw new Exception("#089-04: An error occured while importing YAML dataset '$path'.");
        }

        $application = $this->getApplication();
        $application_option = new Application_Model_Option_Value();

        if(isset($dataset["option"])) {
            $application_option
                ->setData($dataset["option"])
                ->unsData("value_id")
                ->unsData("id")
                ->setData('app_id', $application->getId())
                ->save()
            ;

            if(isset($dataset["topic"])) {
                $new_topic = new Topic_Model_Topic();
                $new_topic
                    ->setData($dataset["topic"])
                    ->setData("value_id", $application_option->getId())
                    ->unsData("id")
                    ->unsData("topic_id")
                    ->save()
                ;

                if(isset($dataset["topic_categories"])) {
                    $old_new_ids = array();
                    $topic_categories = array();

                    /** Create the new ones */
                    foreach($dataset["topic_categories"] as $category) {
                        $new_topic_category = new Topic_Model_Category();
                        $new_topic_category
                            ->setData($category)
                            ->setData("topic_id", $new_topic->getId())
                            ->unsData("category_id")
                            ->unsData("id")
                            ->unsData("parent_id")
                            ->save()
                        ;

                        $new_topic_category->setData("parent_id", $category["parent_id"]);
                        $topic_categories[] = $new_topic_category;

                        $old_id = $category["category_id"];
                        $new_id = $new_topic_category->getId();
                        $old_new_ids[$old_id] = $new_id;
                    }

                    /** Re-associate ids */
                    foreach($topic_categories as $topic_category) {
                        $old_parent_id = $topic_category->getData("parent_id");
                        if(isset($old_new_ids[$old_parent_id])) {
                            $topic_category
                                ->setParentId($old_new_ids[$old_parent_id])
                                ->save()
                            ;
                        }
                    }
                }
            }

        } else {
            throw new Exception("#089-02: Missing option, unable to import data.");
        }
    }

}

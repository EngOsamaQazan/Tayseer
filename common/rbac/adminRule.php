<?php
namespace common\rbac;

use yii\rbac\Rule;
//use app\models\Post;

/**
 * Checks if authorID matches user passed via params
 */
class adminRule extends Rule
{
    public $name = 'admin';

    /**
     * @param string|int $user the user ID.
     * @param Item $item the role or permission that this rule is associated with
     * @param array $params parameters passed to ManagerInterface::checkAccess().
     * @return bool a value indicating whether the rule permits the role or permission it is associated with.
     */
    public function execute($user, $item, $params)
    {
        //return isset($params['post']) ? $params['post']->createdBy == $user : false;
    }
}
<?php
/**
 * Authority: A simple and flexible authorization system for PHP.
 *
 * @package Authority
 */
namespace Authority;

/**
 * Authority allows for establishing rules to check against for authorization
 *
 * @package Authority
 */
class Authority
{
    /**
     * @var mixed Current user in the application for rules to apply to
     */
    protected $currentUser;

    /**
     * @var RuleRepository Collection of rules
     */
    protected $rules;

    /**
     * @var array List of aliases for groups of actions
     */
    protected $aliases = array();

    /**
     * Authority constructor
     *
     * @param mixed $currentUser Current user in the application
     */
    public function __construct($currentUser)
    {
        $this->rules = new RuleRepository;
        $this->setCurrentUser($currentUser);
    }

    /**
     * Determine if current user can access the given action and resource
     *
     * @return boolean
     */
    public function can($action, $resource, $resourceValue = null)
    {
        if ( ! is_string($resource)) {
            $resourceValue = $resource;
            $resource = get_class($resourceValue);
        }

        $rules = $this->getRulesFor($action, $resource);

        $self = $this;

        if (! $rules->isEmpty()) {
            $allowed = array_reduce($rules->all(), function($result, $rule) use ($self, $resourceValue) {
                $result = $result && $rule->isAllowed($self, $resourceValue);
                return $result;
            }, true);
        } else {
            $allowed = false;
        }

        return $allowed;
    }

    /**
     * Determine if current user cannot access the given action and resource
     * Returns negation of can()
     *
     * @return boolean
     */
    public function cannot($action, $resource, $resourceValue = null)
    {
        return ! $this->can($action, $resource, $resourceValue);
    }

    /**
     * Define privilege for a given action and resource
     *
     * @param string        $action Action for the rule
     * @param mixed         $resource Resource for the rule
     * @param Closure|null  $condition Optional condition for the rule
     * @return Rule
     */
    public function allow($action, $resource, $condition = null)
    {
        return $this->addRule(true, $action, $resource, $condition);
    }

    /**
     * Define restriction for a given action and resource
     *
     * @param string        $action Action for the rule
     * @param mixed         $resource Resource for the rule
     * @param Closure|null  $condition Optional condition for the rule
     * @return Rule
     */
    public function deny($action, $resource, $condition = null)
    {
        return $this->addRule(false, $action, $resource, $condition);
    }

    /**
     * Define rule for a given action and resource
     *
     * @param boolean       $allow True if privilege, false if restriction
     * @param string        $action Action for the rule
     * @param mixed         $resource Resource for the rule
     * @param Closure|null  $condition Optional condition for the rule
     * @return Rule
     */
    public function addRule($allow, $action, $resource, $condition = null)
    {
        $rule = new Rule($allow, $action, $resource, $condition);
        $this->rules->add($rule);
        return $rule;
    }

    /**
     * Define new alias for an action
     *
     * @param string $name Name of action
     * @param array  $actions Actions that $name aliases
     * @return RuleAlias
     */
    public function addAlias($name, $actions)
    {
        $alias = new RuleAlias($name, $actions);
        $this->aliases[$name] = $alias;
        return $alias;
    }

    /**
     * Set current user
     *
     * @param mixed $currentUser Current user in the application
     * @return void
     */
    public function setCurrentUser($currentUser)
    {
        $this->currentUser = $currentUser;
    }

    /**
     * Returns all rules relevant to the given rule and resource
     *
     * @return RuleRepository
     */
    public function getRulesFor($action, $resource)
    {
        $aliases = $this->getAliasesForAction($action);
        return $this->rules->reduce(function($rules, $currentRule) use ($aliases) {
            if (in_array($currentRule->getAction(), $aliases)) {
                $rules[] = $currentRule;
            }
            return $rules;
        });
    }

    /**
     * Returns the current rule set
     *
     * @return RuleRepository
     */
    public function getRules()
    {
        return $this->rules;
    }

    /**
     * Returns all actions a given action applies to
     *
     * @return array
     */
    public function getAliasesForAction($action)
    {
        $actions = array($action);

        foreach ($this->aliases as $key => $alias) {
            if ($alias->includes($action)) {
                $actions[] = $key;
            }
        }

        return $actions;
    }

    /**
     * Returns all aliases
     *
     * @return array
     */
    public function getAliases()
    {
        return $this->aliases;
    }

    /**
     * Returns a RuleAlias for a given action name
     *
     * @return RuleAlias|null
     */
    public function getAlias($name)
    {
        return $this->aliases[$name];
    }

    /**
     * Returns current user
     *
     * @return mixed
     */
    public function getCurrentUser()
    {
        return $this->currentUser;
    }
}

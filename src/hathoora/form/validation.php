<?php
namespace hathoora\form;

use hathoora\container;

class validation
{
    /**
     * Validate $arrValidations by matching it against $arrForm
     * This function also trims 
     *
     * @param arr $arrValidations array of fields to check for errors
     *  ex of format:  $arrValidations = array (
     *                                      'first_name' => array ('len_min' => 2, 'desc' => 'Please enter a valid first name'),
     *                                      'phone' => array ('len_min' => 2, 'numeric' => true)
     * @param arr $matchAgainst usually the user submitted form against whom we are checking, can also be an object
     * @return mixed returns true when no errors, returns an array on errors when has errors
     */
    public static function validate($arrValidations, &$matchAgainst, $doTrim = true, $usetranslation = false)
    {
        $arrErrors = array();

        if (is_array($matchAgainst))
            $arrForm =& $matchAgainst;
        // when it is an object and subclass of modelSAR
        else if (is_object($matchAgainst) && is_subclass_of($matchAgainst, '\hathoora\model\modelSAR'))
            $arrForm = $matchAgainst->getSARFields(false, true); // include empty & non object properties
        else
            $arrForm = array();

        if (is_array($arrValidations))
        {
            foreach ($arrValidations as $field => $arrField)
            {
                // any filters to apply?
                if (!empty($arrField['filters']) && is_array($arrField['filters']))
                {
                    foreach($arrField['filters'] as $filter => $arrFilter)
                    {
                        if (!isset($arrForm[$field]))
                            $arrForm[$field] = null;

                        $_returnValue = call_user_func_array($arrFilter, array($arrForm[$field], &$arrForm));
                        if ($_returnValue != null)
                            $arrForm[$field] = $_returnValue;
                    }
        
                    // update arrForm
                    if (is_object($matchAgainst) && is_subclass_of($matchAgainst, '\hathoora\model\modelSAR'))
                        $arrForm = $matchAgainst->getSARFields(false, true); // include empty & non object properties
                }
                
                // lets make sure that required fields are present in $arrForm
                if (!empty($arrField['required']) && $arrField['required'] == true && (!isset($arrForm[$field]) || mb_strlen($arrForm[$field]) < 1))
                    $arrForm[$field] = '';
            }
        }
        
        // now loop over the form array for validation
        if (is_array($arrForm))
        {
            foreach ($arrForm as $field => $input)
            {
                // if there is no validation array then no need fo check
                if (!isset($arrValidations[$field]) || !is_array($arrValidations[$field]))
                    continue;

                if ($doTrim)
                {
                    // always trim the input value
                    if (is_array($arrForm[$field]))
                    {
                        // @todo array walk trim
                        // $arrForm[$field] = array_walk_recursive($arrForm[$field], 'trim');
                    }
                    else if (is_object($arrForm[$field]))
                    {
                        //@todo object walk trim
                    }
                    else
                        $arrForm[$field] = @trim($arrForm[$field]);
                }

                $input = $arrForm[$field];
                
                // validation array
                $arrValidation =& $arrValidations[$field];
                
                // mark all fields as required by default
                $required = isset($arrValidation['required']) ? : false;
                
                $hasValidationRules = isset($arrValidation['rules']) && is_array($arrValidation['rules']);
                
                // however, when a field is enter, but its not required and has validation rules,
                // we do want to check for errors on that field
                if (is_array($input) || is_object($input))
                    $inputSrtLen = 1;
                else
                    $inputSrtLen = mb_strlen($input);
                if ($inputSrtLen > 0 && $hasValidationRules)
                    $required = true;
                
                // if its not required and is empty then lets move on to the next field.
                if (!$required)
                    continue;
                
                // validation rules
                if ($hasValidationRules)
                {
                    foreach ($arrValidation['rules'] as $arrRuleSet)
                    {
                        $ruleSetHasError = false;
                        $ruleSetError = null;
                        
                        // empty value?
                        if ($inputSrtLen == 0)
                        {
                            $ruleSetHasError = true;
                            // get error message:
                            if (isset($arrRuleSet['message']))
                            {
                                $ruleSetError = $arrRuleSet['message'];
                                if ($usetranslation && container::hasService('translation'))
                                    $ruleSetError =  container::getService('translation', array($ruleSetError, array('field' => $field)));
                            }
                            else
                            {
                                if ($usetranslation && container::hasService('translation'))
                                    $arrErrors[$field] =  container::getService('translation', array('validation_field_value_empty', array('field' => $field)));
                                else
                                    $ruleSetError = container::getConfig('hathoora.validation.messages.validation_field_value_empty');
                            }
                        }

                        if (!$ruleSetHasError)
                        {
                            // loop over rule
                            foreach ($arrRuleSet as $rule => $rule_v)
                            {
                                // if already has error then skip
                                if ($ruleSetHasError)
                                    continue;
                                    
                                // skip message
                                if ($rule == 'message')
                                    continue;
                                
                                $ruleHasNoError = true; // no errors by default
                                
                                // if $rule_v is strickly bool, then we don't pass them to validate function
                                if (is_bool($rule_v) === true)
                                   $ruleHasNoError = validationRules::$rule($input);
                                else 
                                {
                                    // any callback?
                                    if ($rule == 'callback')
                                    {
                                        $callbackError = call_user_func_array($rule_v, array($input, &$arrForm));
                                        if ($callbackError !== true)
                                        {
                                            $ruleSetHasError = true;
                                            $ruleSetError = $callbackError;
                                        }
                                    }
                                    else
                                        $ruleHasNoError = validationRules::$rule($input, $rule_v);
                                }
                                
                                if ($ruleHasNoError == false)
                                {
                                    $ruleSetHasError = true;
                                    
                                    if (isset($arrRuleSet['message']))
                                    {
                                        $ruleSetError = $arrRuleSet['message'];

                                        if ($usetranslation && container::hasService('translation'))
                                            $ruleSetError =  container::getService('translation', array($ruleSetError, array('field' => $field)));
                                    }
                                }
                            }
                        }
                        
                        // has errors but no error emssage
                        if ($ruleSetHasError)
                        {
                            if ($ruleSetError)
                                $arrErrors[$field] =  $ruleSetError;
                            else
                            {
                                if ($usetranslation && container::hasService('translation'))
                                    $arrErrors[$field] =  container::getService('translation', array($ruleSetError, array('field' => $field)));
                                else
                                    container::getConfig('hathoora.validation.messages.validation_field_general_error');
                            }
                        }
                    }
                }
            }
        }
        else
        {
            // consider error
            if ($usetranslation && container::hasService('translation'))
                $arrErrors = array(container::getService('translation', array('validation_empty_form_submitted_error')));
            else
                $arrErrors = array(container::getConfig('hathoora.validation.messages.validation_empty_form_submitted_error'));
        }
            
        if (count($arrErrors))
            return $arrErrors; // has errors
        else
            return true; // no errors
    }
}
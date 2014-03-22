<?php
namespace hathoora\translation
{
    use hathoora\model\modelSAR,
        hathoora\grid\grid;

    class translator extends modelSAR
    {
        /**
         * stores cache service to use
         * \hathoora\cache\cache
         */
        private $cacheService;

        /**
         * private cache time
         */
        private $cacheTime = 86400;

        /**
         * Show empty tks
         * @var bool
         */
        private $showEmptyTks = true;

        /**
         * @var array of supported languages
         */
        private $arrLanguages = array();

        /**
         * default language
         */
        private $defaultLanguage;

        /**
         * language selected by user
         */
        private $translationLanguage;

        /**
         * For debugging
         * @var
         */
        private $debugSettings;

        /**
         * @var \hathoora\database\db
         */
        private $db;

        /**
         * Translator constructor
         */
        public function __construct()
        {}

        public function setTKConfig($arrHathooraTKConfig = array())
        {
            $dsn = null;

            if (is_array($arrHathooraTKConfig) && count($arrHathooraTKConfig))
            {
                // which db to use?
                if (!empty($arrHathooraTKConfig['dsn']))
                    $dsn = $arrHathooraTKConfig['dsn'];

                // set cache time
                if (!empty($arrHathooraTKConfig['cache_time']))
                    $this->cacheTime = $arrHathooraTKConfig['cache_time'];

                // set cache time
                if (isset($arrHathooraTKConfig['show_empty']))
                    $this->showEmptyTks = $arrHathooraTKConfig['show_empty'];

                if (isset($arrHathooraTKConfig['default_language']))
                    $this->defaultLanguage = $arrHathooraTKConfig['default_language'];
                else
                    $this->defaultLanguage = 'en_US';

                if (isset($arrHathooraTKConfig['debug']))
                    $this->debugSettings = $arrHathooraTKConfig['debug'];

                // array of supported languages
                if (isset($arrHathooraTKConfig['languages']) && is_array($arrHathooraTKConfig['languages']))
                    $this->arrLanguages = $arrHathooraTKConfig['languages'];
            }

            $this->db = $this->getDBConnection($dsn);
            $this->setTranslationLanguage($this->getRequest()->sessionParam('language'));
        }

        /**
         * Return supported languages
         */
        public function getLanguages()
        {
            return $this->arrLanguages;
        }

        /**
         * Returns default language when none is selected by user
         * @return mixed
         */
        public function getDefaultLanguage()
        {
            return $this->defaultLanguage;
        }

        /**
         * Returns user's selected language if any, otherwise default language
         */
        public function getTranslationLanguage()
        {
            $lang = $this->getDefaultLanguage();
            if ($this->translationLanguage)
                $lang = $this->translationLanguage;

            return $lang;
        }

        /**
         * Set translation language
         */
        public function setTranslationLanguage($lang)
        {
            $this->translationLanguage = $lang;
        }

        /**
         * Returns true when we are in tk debugging
         */
        private function isTkDebug()
        {
            $status = false;
            if (is_array($this->debugSettings) && isset($this->debugSettings['method']) && isset($this->debugSettings['parameter']))
            {
                $method = strtolower($this->debugSettings['method']);
                $parameter = $this->debugSettings['parameter'];

                if ($method == 'get')
                    $status = $this->getRequest()->getParam($parameter);
                else if ($method == 'header')
                    $status = $this->getRequest()->serverParam('HTTP_' . strtoupper($parameter));
            }

            return $status;
        }

        /**
         * Set dic cache service
         */
        public function setCacheService($cacheService)
        {
            $this->cacheService = $cacheService;
        }

        /**
         * Returns cache service
         */
        public function getCacheService()
        {
            return $this->cacheService;
        }

        /**
         * Returns the value of token
         *
         * @param string $token that we want to get the value for ex: {{name}}
         * @param array $arrTokens that has a key of token ex: 'name' = 'xyz'
         */
        public function deTokenize($token, &$arrTokens)
        {
            return \hathoora\helper\stringHelper::deTokenize($token, $arrTokens);
        }

        /**
         * Get cache key for a translation
         *
         * @param $tk
         * @param lang
         */
        public function getTranslationCacheKey($tk, $lang)
        {
            return 'translation:key:' . $tk . ':'. $lang;
        }

        /**
         * Get cache key for a route
         *
         * @param $tk
         * @param lang
         */
        public function getRouteCacheKey($route, $lang)
        {
            return 'translation:route:' . $route . ':'. $lang;
        }

        /**
         * Translate a translation key
         *
         * @param $tk
         * @param null $arrToken
         * @param null $lang
         * @param bool $reSeed
         * @return null|string
         */
        public function t($tk, $arrToken = null, $lang = null, $reSeed = false)
        {
            if ($this->isTkDebug())
                return $tk;

            $tk = trim($tk);
            $isEmptyTk = $translation = $cacheKey = $translationDB =  null;
            $cacheService = $this->getCacheService();
            if (is_null($lang))
                $lang =  $this->getTranslationLanguage();

            if ($cacheService)
            {
                $cacheKey = $this->getTranslationCacheKey($tk, $lang);
                if (!$reSeed)
                {
                    $translationDB = $cacheService->get($cacheKey);
                    if (is_array($translationDB) && isset($translationDB['translation']))
                        $translation = $translationDB['translation'];
                }
            }

            if ((is_null($translation) && !is_array($translationDB)) || $reSeed == true)
            {
                try
                {
                    if ($reSeed && $cacheKey)
                        $this->db->comment('Reseeding translation cache for ' . $cacheKey);

                    $translationDB = $this->db->fetchArray(
                                   'SELECT translation, tk.translation_id
                                    FROM translation_key tk
                                    INNER JOIN translation_value tv ON (tk.translation_id = tv.translation_id)
                                    WHERE
                                        tk.translation_key = "?" AND
                                        tv.language = "?"
                                    LIMIT 1', array($tk, $lang));
                }
                catch(\Exception $e)
                {
                }

                if (is_array($translationDB) && isset($translationDB['translation']))
                    $translation = $translationDB['translation'];
                // create a fake array so we can cache it and don't have to make db calls
                else
                    $translationDB = array('translation_id' => 0);

                // cache it
                if ($cacheService)
                    $cacheService->set($cacheKey, $translationDB, $this->cacheTime);
            }

            if (is_null($translation))
            {
                $translation = $tk;
                $isEmptyTk = true;
            }

            // show empty tks
            if (
                    // considered empty tk
                    $isEmptyTk &&
                    !$this->showEmptyTks &&
                    is_array($translationDB) && isset($translationDB['translation_id'])
            )
            {
                $translation = '';
            }

            // detokenize
            $translation = $this->deTokenize($translation, $arrToken);

            return $translation;
        }

        /**
         * Functions returns an array of route translations
         *
         * @param string $route name
         * @param array $arrTranslationkeys
         *      This is an array of array of keys which contains array of tokens for individual translation keys e.g.
         *      array(
         *              item_1 => array(
         *                                  token_1 => value_1,
         *                                  token_2 => value_2),
         *              item_2 => array(
         *                                  token_3 => value_3))
         *
         *      In this example item_1 would be using token_1 & token_2
         * @param null $lang
         * @param bool $reSeed
         * @param string $lang
         * @return array|null
         */
        public function getRouteTranslations($route, $arrTranslationkeys = null, $lang = null, $reSeed = false)
        {
            $route = trim($route);
            $arrTranslations = $cacheKey = $translationsDB = null;
            $cacheService = $this->getCacheService();
            if (is_null($lang))
                $lang =  $this->getTranslationLanguage();

            if ($cacheService)
            {
                $cacheKey = $this->getRouteCacheKey($route, $lang);
                if (!$reSeed)
                {
                    $translationsDB = $cacheService->get($cacheKey);
                    if (is_array($translationsDB) && isset($translationsDB['translation_keys']))
                        $arrTranslations = $translationsDB['translation_keys'];
                }
            }

            if ((is_null($arrTranslations) && !is_array($translationsDB)) || $reSeed == true)
            {
                try
                {
                    if ($reSeed && $cacheKey)
                        $this->db->comment('Reseeding route translation cache for ' . $cacheKey);

                    $stmt = $this->db->query('SELECT translation_key, translation
                                    FROM translation_key tk
                                    INNER JOIN translation_value tv ON (tk.translation_id = tv.translation_id)
                                    INNER JOIN translation_route tr ON (tk.translation_id = tr.translation_id)
                                    WHERE
                                        tr.route = "?" AND
                                        tv.language = "?"',
                                    array($route, $lang));
                    if ($stmt && $stmt->rowCount())
                    {
                        $translationsDB = array();
                        while ($row = $stmt->fetchArray())
                        {
                            $translationsDB['translation_keys'][$row['translation_key']] = $row['translation'];
                        }
                    }
                }
                catch(\Exception $e)
                {
                }

                // create a fake array so we can cache empty results
                if (!is_array($translationsDB))
                    $translationsDB = array('translation_keys' => array());

                $arrTranslations = $translationsDB['translation_keys'];

                // cache it
                if ($cacheService)
                    $cacheService->set($cacheKey, $translationsDB, $this->cacheTime);
            }

            // debugging?
            if ($this->isTkDebug())
                return $arrTranslations;

            // do we need to replace any tokens?
            if (is_array($arrTranslationkeys))
            {
                $foundDbTks = is_array($arrTranslations);
                foreach($arrTranslationkeys as $tk => $arrTkTokens)
                {
                    if ($foundDbTks && isset($arrTranslations[$tk]))
                        $arrTranslations[$tk] = $this->deTokenize($arrTranslations[$tk], $arrTkTokens);
                    else if ($this->showEmptyTks)
                        $arrTranslations[$tk] = $this->deTokenize($tk, $arrTkTokens);
                }
            }

            return $arrTranslations;
        }

        ####################################################
        #
        #       Functions for storing
        #
        /**
         * Returns information about translation_keys that have same key
         *
         * @param $id
         * @return mixed
         */
        public function info($id)
        {
            $arrTranslations = null;

            $query = '
                SELECT
                    tk.*,
                    tv.language,
                    tv.translation,
                    tr.route
                FROM translation_key tk
                INNER JOIN translation_value tv ON (tk.translation_id = tv.translation_id)
                LEFT JOIN translation_route tr ON (tk.translation_id = tr.translation_id)
                WHERE
                    tk.translation_id = "?"';
            try
            {
                $stmt = $this->db->query($query, array($id));
                if ($stmt && $stmt->rowCount())
                {
                    while ($row = $stmt->fetchArray())
                    {
                        if (!is_array($arrTranslations))
                        {
                            $arrTranslations = array(
                                'translation_id' => $row['translation_id'],
                                'translation_key' => $row['translation_key'],
                                'notes' => $row['notes']);
                        }

                        $arrTranslations['languages'][$row['language']] = $row['translation'];
                        if ($row['route'])
                            $arrTranslations['routes'][$row['route']] = $row['route'];
                    }
                }
            }
            catch(\Exception $e)
            {
            }

            return $arrTranslations;
        }

        /**
         * Function for storing..
         *
         * @param bool $do
         * @param bool $arrForm
         * @return mixed|void
         */
        public function store($do, &$arrForm)
        {
            $do = strtolower($do);
            $arrReturn = $arrErrors = array();
            $success = null;
            $dbError = 'Database error, check the logs.';

            // validation: make sure translation_id is not missing for edit & delete
            if ($do == 'edit' || $do == 'delete')
            {
                if (empty($arrForm['translation_id']))
                    $arrErrors['translation_id'] = 'Translation id is missing.';
                // make sure translation_id is id to prevent injections..
                else if (is_int($arrForm['translation_id']))
                    $arrErrors['translation_id'] = 'Translation id is not a valid integer.';
            }

            if ($do == 'add' || $do == 'edit')
            {
                // trim translation_key
                if (isset($arrForm['translation_key']))
                    $arrForm['translation_key'] = trim($arrForm['translation_key']);

                // set notes, it not already set
                if (!isset($arrForm['notes']))
                    $arrForm['notes'] = null;

                // validation: translation_key validation to enforce lowercase & no spaces
                if (!preg_match('/^[a-z0-9_]+$/', $arrForm['translation_key']))
                    $arrErrors['translation_key'] = 'Translation key can only have lower case, numbers and underscores.';
                else if (strlen($arrForm['translation_key']) > 105)
                    $arrErrors['translation_key'] = 'Translation key cannot be more than 105 characters long.';

                // validation: make sure translations are not empty
                if (!isset($arrForm['languages']) || !is_array($arrForm['languages']) || !count($arrForm['languages']))
                    $arrErrors['translation_key'] = 'Please enter translation(s).';
                else if (is_array($arrForm['languages']))
                {
                    foreach ($arrForm['languages'] as $lang => $translation)
                    {
                        $translation = trim($arrForm['languages'][$lang]);

                        if (!strlen($translation))
                            $arrErrors['languages['. $lang  .']'] = $lang . ' translation cannot be empty.';
                    }
                }

                // validation: check for duplicate
                if (!count($arrErrors))
                {
                    $itemExists = $this->translationKeyExists($arrForm['translation_key']);
                    if ($itemExists)
                    {
                        if (
                            ($do == 'add') ||
                            ($do == 'edit' && $itemExists != $arrForm['translation_id'])
                        )
                            $arrErrors['translation_key'] = $arrForm['translation_key'] . ' already exists.';
                    }
                }
            }
            // when deleting also check if key exists
            else if ($do == 'delete')
            {
                if (!count($arrErrors))
                {
                    $translationKey = $this->getTranslationKeyFromId($arrForm['translation_id']);
                    if ($translationKey)
                        $arrForm['translation_key'] = $translationKey;
                    else
                        $arrErrors['translation_key'] = 'Unable to find translation id <b>'. (int) $arrForm['translation_id'] .'</b>.';
                }
            }
            else
                $arrErrors[] = 'Incorrect parameters passed';

            // no errors, lets proceed.
            if (!count($arrErrors))
            {
                if ($do == 'delete')
                {
                    $rollBack = false;
                    try
                    {
                        $this->db->beginTransaction();

                        // delete from translation key
                        $query = 'DELETE FROM translation_key WHERE translation_id = ?';
                        $queryParams = array($arrForm['translation_id']);

                        $stmt = $this->db->query($query, $queryParams);
                        if ($stmt)
                        {
                            // delete without caring for the status
                            $this->db->query('DELETE FROM translation_value WHERE translation_id = ?', $queryParams);
                            $this->db->query('DELETE FROM translation_route WHERE translation_id = ?', $queryParams);
                        }
                        else
                        {
                            $rollBack = true;
                        }

                    }
                    catch (\Exception $e)
                    {
                        $dbError = $e->getMessage();
                        $rollBack = true;
                    }

                    if ($rollBack)
                    {
                        $arrErrors['mysql'] = $dbError;
                        $this->db->rollback();
                    }
                    else
                    {
                        $success = '<b>' . $arrForm['translation_key'] . '</b> has been deleted.';
                        $this->db->commit();
                    }
                }
                else if ($do == 'add' || $do == 'edit')
                {
                    $rollBack = false;
                    try
                    {
                        $this->db->beginTransaction();

                        // add|update translation_key
                        if ($do == 'add')
                        {
                            $query = 'INSERT INTO translation_key SET translation_key = "?", notes = "?"';
                            $queryParams = array($arrForm['translation_key'], $arrForm['notes']);
                        }
                        else if ($do == 'edit')
                        {
                            $query = 'UPDATE translation_key SET translation_key = "?", notes = "?" WHERE translation_id = ? LIMIT 1';
                            $queryParams = array($arrForm['translation_key'], $arrForm['notes'], $arrForm['translation_id']);
                        }

                        $stmt = $this->db->query($query, $queryParams);
                        // next add|update translation_value for each language
                        if ($stmt)
                        {
                            if ($do == 'add')
                                $arrForm['translation_id'] = $stmt->lastInsertId();

                            $query = null;
                            $queryParams = array();

                            foreach ($arrForm['languages'] as $_lang => $_translation)
                            {
                                if ($query) $query .= ', ';
                                    $query .= '("?", "?", "?") ';

                                $queryParams[] = $arrForm['translation_id'];
                                $queryParams[] = $_lang;
                                $queryParams[] = html_entity_decode($arrForm['languages'][$_lang]);
                            }

                            $query = 'REPLACE INTO translation_value (translation_id, language, translation) VALUES ' . $query;

                            $stmt = $this->db->query($query, $queryParams);
                            // next is it to add|update routes
                            if ($stmt)
                            {
                                // delete old routes
                                $this->db->query('DELETE FROM translation_route WHERE translation_id = ?', array($arrForm['translation_id']));

                                if (isset($arrForm['routes']) && is_array($arrForm['routes']) && count($arrForm['routes']))
                                {
                                    $query = null;
                                    $queryParams = array();

                                    foreach ($arrForm['routes'] as $route)
                                    {
                                        if ($query) $query .= ', ';
                                            $query .= '("?", "?") ';

                                        $queryParams[] = $arrForm['translation_id'];
                                        $queryParams[] = $route;
                                    }

                                    $query = 'INSERT INTO translation_route (translation_id, route) VALUES ' . $query;
                                    $stmt = $this->db->query($query, $queryParams);
                                    if ($stmt)
                                    {
                                        // do nothing..
                                    }
                                    else
                                        $rollBack = true;
                                }

                            }
                            else
                                $rollBack = true;
                        }
                        else
                        {
                            $rollBack = true;
                        }

                    }
                    catch (\Exception $e)
                    {
                        $dbError = $e->getMessage();
                        $rollBack = true;
                    }

                    if ($rollBack)
                    {
                        $arrErrors['mysql'] = $dbError;
                        $this->db->rollback();
                    }
                    else
                    {
                        $success = '<b>' . $arrForm['translation_key'] . ' </b> has been ' . ($do == 'add' ? 'added' : 'updated');
                        $this->db->commit();
                    }
                }
            }


            if (!count($arrErrors) && $success)
            {
                // invalidate/reseed cache...
                if ($cacheService = $this->getCacheService())
                {
                    // invalidate cache key itself in all languages
                    $arrLanguages = (array) $this->getLanguages() + (isset($arrForm['languages']) ? array_keys($arrForm['languages']) : array());
                    foreach ($arrLanguages as $lang)
                    {
                        $this->t($arrForm['translation_key'], null, $lang, true);
                    }

                    // only invalidate route
                    if (isset($arrForm['routes']) && is_array($arrForm['routes']) && count($arrForm['routes']))
                    {
                        foreach($arrForm['routes'] as $route)
                        {
                            foreach ($arrLanguages as $lang)
                            {
                                $cacheKey = $this->getRouteCacheKey($route, $lang);
                                $this->cacheService->delete($cacheKey);
                            }
                        }
                    }
                }

                $arrReturn['translation_id'] = $arrForm['translation_id'];
                $arrReturn['status'] = 'success';
                $arrReturn['message'] = $success;
            }
            else
            {
                if (!count($arrErrors))
                    $arrErrors['database'] = $dbError;

                $arrReturn['status'] = 'error';
                $arrReturn['message'] = $arrErrors;
            }

            return $arrReturn;
        }

        /**
         * Returns translation_id when a key already exists
         *
         */
        public function translationKeyExists($tk)
        {
            $translation_id = null;

            try
            {
                $translation_id = $this->db->fetchValue('SELECT translation_id FROM translation_key WHERE translation_key = "?"', array($tk));
            }
            catch (\Exception $e)
            {
                //
            }

            return $translation_id;
        }

        /**
         * Returns translation key given an id
         */
        public function getTranslationKeyFromId($id)
        {
            $tk = null;

            try
            {
                $tk = $this->db->fetchValue('SELECT translation_key FROM translation_key WHERE translation_id = ?', array($id));
            }
            catch (\Exception $e)
            {
                //
            }

            return $tk;
        }


        ####################################################
        #
        #               Grid
        #
        /**
         * grid function
         *
         * @param array $arrParams
         * @param bool $render grid
         * @return array|mixed
         */
        public function grid($arrParams = array(), $render = false)
        {
            $arrParams['queryTotal'] = '
                SELECT
                    COUNT(tk.translation_id) as total
                FROM translation_key tk ' .
                (!empty($arrParams['joinTotal'])  ? grid::sqlBuildJoin($arrParams['joinTotal']) : null).
                (!empty($arrParams['whereTotal']) ? grid::sqlBuildWhere($arrParams['whereTotal']) : null) .
                (!empty($arrParams['groupTotal']) ? grid::sqlBuildGroupBy($arrParams['groupTotal'], true) : null);

            $selectField = null;
            if (!empty($arrParams['selectField']))
            {
                $selectField = grid::sqlBuildSelect($arrParams['selectField']);
                if ($selectField) $selectField = ', ' . $selectField;
            }

            $arrParams['queryRow'] = '
                SELECT
                    tk.translation_id ' .
                    $selectField .'
                FROM translation_key tk ' .
                (!empty($arrParams['joinRow']) ? grid::sqlBuildJoin($arrParams['joinRow']) : null) .
                (!empty($arrParams['whereRow']) ? grid::sqlBuildWhere($arrParams['whereRow']) : null) .
                (!empty($arrParams['groupRow']) ? grid::sqlBuildGroupBy($arrParams['groupRow'], true) : null);

            if (empty($arrParams['primaryKey']))
                $arrParams['primaryKey'] = null;

            return grid::sqlRun($arrParams, $render);
        }
    }
}
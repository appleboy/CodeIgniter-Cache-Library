<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Cache Class
 *
 * Caching library for CodeIgniter
 *
 * @category    Libraries
 * @author      Bo-Yi Wu
 * @link        https://github.com/appleboy/CodeIgniter-Cache-Library
 * @version     1.0
 */

class Lib_cache
{
    private $_ci;
    private $_default_expires;

    /**
     * Constructor - Initializes and references CI
     */
    function __construct()
    {
        log_message('debug', "Cache Library Initialized.");

        $this->_ci =& get_instance();
        $this->_ci->load->config('cache');
        $this->_adapter = $this->_ci->config->item('cache_adapter');
        $this->_backup = $this->_ci->config->item('cache_backup');
        $this->_ci->load->driver('cache', array('adapter' => $this->_adapter, 'backup' => $this->_backup));

        $this->_default_expires = $this->_ci->config->item('cache_default_expires');
    }

    /**
     * Call a library's cached result or create new cache
     *
     * @access  public
     * @param   string
     * @return  array
     */
    public function library($library, $method, $arguments = array(), $expires = null)
    {
        if (!class_exists(ucfirst($library))) {
            $this->_ci->load->library($library);
        }

        return $this->_call($library, $method, $arguments, $expires);
    }

    /**
     * Call a model's cached result or create new cache
     *
     * @access public
     * @return array
     */
    public function model($model, $method, $arguments = array(), $expires = null)
    {
        if (!class_exists(ucfirst($model))) {
            $this->_ci->load->model($model);
        }

        return $this->_call($model, $method, $arguments, $expires);
    }

    private function _call($property, $method, $arguments = array(), $expires = null)
    {
        $this->_ci->load->helper('security');

        if (!$expires or empty($expires)) {
            if ($this->_default_expires == 0) {
                $expires = 365*60*60*24;
            } else {
                $expires = $this->_default_expires;
            }
        }

        if (!is_array($arguments)) {
            $arguments = (array) $arguments;
        }

        $arguments = array_values($arguments);

        $cache_key = do_hash($property . $method . serialize($arguments), 'sha1');

        if ($expires >= 0) {
            $cached_response = $this->_ci->cache->get($cache_key);
        } else {
            $this->_ci->cache->delete($cache_key);

            return;
        }

        if ($cached_response !== false && $cached_response !== null) {
            return $cached_response;
        } else {
            // Call the model or library with the method provided and the same arguments
            $new_response = call_user_func_array(array($this->_ci->$property, $method), $arguments);
            $this->_ci->cache->save($cache_key, $new_response, $expires);

            return $new_response;
        }
    }
}

/* End of file lib_cache.php */
/* Location: ./application/libraries/lib_cache.php */

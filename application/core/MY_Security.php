<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Perbaikan cookie CSRF CodeIgniter 3.1.13 di PHP < 7.3.
 *
 * Bawaan CI menulis header "Path=%2F" (rawurlencode('/')) sehingga browser
 * mengabaikan path cookie: Chrome menyimpan cookie per folder halaman (token
 * jadi basi) dan Safari iOS tidak mengirimkannya kembali (login "not allowed").
 */
class MY_Security extends CI_Security {

    public function csrf_set_cookie() {
        $expire        = time() + $this->_csrf_expire;
        $secure_cookie = (bool) config_item('cookie_secure');

        if ($secure_cookie && ! is_https()) {
            return FALSE;
        }

        $domain = trim((string) config_item('cookie_domain'));
        $path   = config_item('cookie_path') ? config_item('cookie_path') : '/';

        header('Set-Cookie: ' . $this->_csrf_cookie_name . '=' . $this->_csrf_hash
            . '; Expires=' . gmdate('D, d M Y H:i:s', $expire) . ' GMT'
            . '; Max-Age=' . $this->_csrf_expire
            . '; Path=' . $path
            . ($domain === '' ? '' : '; Domain=' . $domain)
            . ($secure_cookie ? '; Secure' : '')
            . (config_item('cookie_httponly') ? '; HttpOnly' : '')
            . '; SameSite=Lax', FALSE);

        log_message('info', 'CSRF cookie sent');

        return $this;
    }
}

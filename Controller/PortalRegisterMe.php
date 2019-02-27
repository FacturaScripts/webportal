<?php
/**
 * This file is part of webportal plugin for FacturaScripts.
 * Copyright (C) 2018 Carlos Garcia Gomez <carlos@facturascripts.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */
namespace FacturaScripts\Plugins\webportal\Controller;

use FacturaScripts\Core\App\AppSettings;
use FacturaScripts\Core\Base\ControllerPermissions;
use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Dinamic\Lib\EmailTools;
use FacturaScripts\Dinamic\Lib\IPFilter;
use FacturaScripts\Dinamic\Model\Contacto;
use FacturaScripts\Dinamic\Model\User;
use FacturaScripts\Plugins\webportal\Lib\WebPortal\GeoLocation;
use FacturaScripts\Plugins\webportal\Lib\WebPortal\PortalController;
use Symfony\Component\HttpFoundation\Response;

/**
 * Description of PortalRegisterMe
 *
 * @author Francesc Pineda Segarra      <francesc.pineda@x-netdigital.com>
 * @author Cristo M. Estévez Hernández  <cristom.estevez@gmail.com>
 */
class PortalRegisterMe extends PortalController
{

    /**
     *
     * @var IPFilter
     */
    protected $ipFilter;

    /**
     * New contact
     *
     * @var Contacto
     */
    protected $newContact;

    /**
     *
     * @var bool
     */
    public $registrationOK = false;

    public function __construct(&$cache, &$i18n, &$miniLog, $className, $uri = '')
    {
        parent::__construct($cache, $i18n, $miniLog, $className, $uri);
        $this->ipFilter = new IPFilter();
    }

    /**
     * Runs the controller's private logic.
     *
     * @param Response              $response
     * @param User                  $user
     * @param ControllerPermissions $permissions
     */
    public function privateCore(&$response, $user, $permissions)
    {
        parent::privateCore($response, $user, $permissions);
        $this->setTemplate('PortalRegisterMe');
    }

    /**
     * Execute the public part of the controller.
     *
     * @param Response $response
     */
    public function publicCore(&$response)
    {
        parent::publicCore($response);
        $this->setTemplate('PortalRegisterMe');

        $action = $this->request->get('action', '');
        $this->execAction($action);
    }

    /**
     * Active the contact using the url sended previously.
     *
     * @return bool
     */
    protected function activeContact()
    {
        $cod = $this->request->get('cod', '');
        $email = $this->request->get('email', '');
        if (empty($email) || empty($cod)) {
            return false;
        }

        $contact = new Contacto();
        $where = [new DataBaseWhere('email', $email)];
        if ($contact->loadFromCode('', $where) && $cod === sha1($contact->idcontacto . $contact->password)) {
            $contact->verificado = true;
            if ($contact->save()) {
                $this->updateCookies($contact, true);
                return true;
            }

            $this->miniLog->error($this->i18n->trans('record-save-error'));
            return false;
        }

        $this->miniLog->error($this->i18n->trans('record-not-found'));
        $this->ipFilter->setAttempt($this->request->getClientIp());
        return false;
    }

    /**
     * Run the actions.
     *
     * @param string $action
     *
     * @return bool
     */
    protected function execAction($action)
    {
        switch ($action) {
            case 'activate':
                if ($this->activeContact()) {
                    $url = empty(AppSettings::get('webportal', 'url')) ? 'EditProfile' : AppSettings::get('webportal', 'url');
                    $this->response->headers->set('Refresh', '0; ' . $url);
                }
                break;

            case 'register':
                $this->newContact = new Contacto();
                $this->registrationOK = $this->registerContact();
                break;
        }
    }

    /**
     *
     * @return bool
     */
    protected function registerContact(): bool
    {
        $email = $this->request->request->get('email');
        if ($this->newContact->loadFromCode('', [new DataBaseWhere('email', $email)])) {
            $this->miniLog->warning($this->i18n->trans('email-contact-already-used'));
            $this->ipFilter->setAttempt($this->request->getClientIp());
            return false;
        }

        $emailData = \explode('@', $email);
        $this->newContact->nombre = empty($this->request->request->get('name')) ? $emailData[0] : $this->request->request->get('name');
        $this->newContact->apellidos = $this->request->request->get('surname', '');
        $this->newContact->descripcion = $this->request->request->get('description', '');
        $this->newContact->email = $email;

        $newPassword = $this->request->request->get('password', '');
        $newPassword2 = $this->request->request->get('password2', '');
        if (empty($newPassword) || $newPassword !== $newPassword2) {
            $this->miniLog->warning($this->i18n->trans('different-passwords', ['%userNick%' => $email]));
            return false;
        }

        $this->newContact->setPassword($newPassword);
        $this->setGeoIpData($this->newContact);

        if ($this->newContact->save()) {
            $url = AppSettings::get('webportal', 'url') . '/PortalRegisterMe?action=activate'
                . '&cod=' . sha1($this->newContact->idcontacto . $this->newContact->password)
                . '&email=' . $this->newContact->email;

            if ($this->sendEmailConfirmation($this->newContact->email, $url)) {
                return true;
            }

            $this->newContact->delete();
            $this->miniLog->alert($this->i18n->trans('try-again'));
            return false;
        }

        $this->miniLog->error($this->i18n->trans('record-not-found'));
        return false;
    }

    /**
     * Send and email with data posted from form.
     *
     * @param string $email
     * @param string $url
     *
     * @return bool
     */
    protected function sendEmailConfirmation(string $email, string $url)
    {
        $emailTools = new EmailTools();

        $mail = $emailTools->newMail();
        $mail->addCC($email);
        $mail->Subject = $this->i18n->trans('confirm-email');

        $params = [
            'body' => '<h1>' . $mail->Subject . '</h1>' . $this->i18n->trans('please-click-on-confirm-email')
            . ' <a href="' . $url . '">' . $mail->Subject . '</a>',
            'company' => AppSettings::get('webportal', 'title'),
            'footer' => AppSettings::get('webportal', 'copyright'),
            'title' => $mail->Subject,
        ];
        $mail->msgHTML($emailTools->getTemplateHtml($params));

        return $emailTools->send($mail);
    }

    /**
     * Set geoIP details to contact.
     *
     * @param Contacto $contact
     */
    private function setGeoIpData(&$contact)
    {
        $ipAddress = $this->request->getClientIp() ?? '::1';
        $geoLocation = new GeoLocation();
        $geoLocation->setGeoIpData($contact, $ipAddress);
    }
}

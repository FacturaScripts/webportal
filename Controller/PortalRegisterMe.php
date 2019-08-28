<?php
/**
 * This file is part of webportal plugin for FacturaScripts.
 * Copyright (C) 2018-2019 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Dinamic\Lib\Email\ButtonBlock;
use FacturaScripts\Dinamic\Lib\Email\NewMail;
use FacturaScripts\Dinamic\Model\Contacto;
use FacturaScripts\Plugins\webportal\Lib\WebPortal\GeoLocation;
use FacturaScripts\Plugins\webportal\Lib\WebPortal\PortalController;
use Symfony\Component\HttpFoundation\Response;

/**
 * Description of PortalRegisterMe
 *
 * @author Carlos García Gómez          <carlos@facturascripts.com>
 * @author Francesc Pineda Segarra      <francesc.pineda@x-netdigital.com>
 * @author Cristo M. Estévez Hernández  <cristom.estevez@gmail.com>
 */
class PortalRegisterMe extends PortalController
{

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
     * Activate the contact using the url sended previously.
     *
     * @return bool
     */
    protected function activateContact()
    {
        $cod = $this->request->get('cod', '');
        $email = $this->request->get('email', '');
        if (empty($email) || empty($cod)) {
            return false;
        }

        $contact = new Contacto();
        $where = [new DataBaseWhere('email', rawurldecode($email))];
        if ($contact->loadFromCode('', $where) && $cod === $this->getActivationCode($contact)) {
            $contact->verificado = true;
            if ($contact->save()) {
                $this->updateCookies($contact, true);
                return true;
            }

            $this->toolBox()->i18nLog()->error('record-save-error');
            return false;
        }

        $this->toolBox()->i18nLog()->error('record-not-found');
        $this->setIPWarning();
        return false;
    }

    /**
     * Run the actions.
     *
     * @param string $action
     *
     * @return bool
     */
    protected function execAction(string $action)
    {
        switch ($action) {
            case 'activate':
                if ($this->activateContact()) {
                    $defaultUrl = $this->toolBox()->appSettings()->get('webportal', 'url');
                    $url = empty($defaultUrl) ? 'EditProfile' : $defaultUrl;
                    $this->redirect($url);
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
     * @param Contacto $contact
     *
     * @return string
     */
    protected function getActivationCode($contact): string
    {
        return sha1($contact->idcontacto . $contact->password);
    }

    /**
     *
     * @return bool
     */
    protected function registerContact(): bool
    {
        if ('true' !== $this->request->request->get('privacy')) {
            $this->toolBox()->i18nLog()->warning('you-must-accept-privacy-policy');
            return false;
        }

        $email = $this->request->request->get('email');
        if ($this->newContact->loadFromCode('', [new DataBaseWhere('email', $email)])) {
            $this->toolBox()->i18nLog()->warning('email-contact-already-used', ['%email%' => $email]);
            $this->setIPWarning();
            return false;
        }

        $emailData = explode('@', $email);
        $this->newContact->nombre = empty($this->request->request->get('name')) ? $emailData[0] : $this->request->request->get('name');
        $this->newContact->apellidos = $this->request->request->get('surname', '');
        $this->newContact->descripcion = $this->request->request->get('description', '');
        $this->newContact->email = $email;
        $this->newContact->aceptaprivacidad = true;
        $this->newContact->newPassword = $this->request->request->get('password', '');
        $this->newContact->newPassword2 = $this->request->request->get('password2', '');
        if (!$this->newContact->test()) {
            return false;
        }

        $this->setGeoIpData($this->newContact);

        if ($this->newContact->save()) {
            if ($this->sendEmailConfirmation($this->newContact)) {
                return true;
            }

            $this->newContact->delete();
            $this->toolBox()->i18nLog()->critical('send-mail-error');
            $this->toolBox()->i18nLog()->warning('try-again-later');
            return false;
        }

        $this->toolBox()->i18nLog()->error('record-save-error');
        return false;
    }

    /**
     * Send and email with data posted from form.
     *
     * @param Contacto $contact
     *
     * @return bool
     */
    protected function sendEmailConfirmation($contact)
    {
        $i18n = $this->toolBox()->i18n();
        $link = $this->toolBox()->appSettings()->get('webportal', 'url') . '/PortalRegisterMe?action=activate'
            . '&cod=' . $this->getActivationCode($contact)
            . '&email=' . rawurlencode($contact->email);

        $mail = new NewMail();
        $mail->fromName = $this->toolBox()->appSettings()->get('webportal', 'title');
        $mail->addAddress($contact->email);
        $mail->title = $i18n->trans('confirm-email');
        $mail->text = $i18n->trans('please-click-on-confirm-email');
        $mail->addMainBlock(new ButtonBlock($i18n->trans('confirm-email'), $link));
        return $mail->send();
    }

    /**
     * Set geoIP details to contact.
     *
     * @param Contacto $contact
     */
    private function setGeoIpData(&$contact)
    {
        $ipAddress = $this->toolBox()->ipFilter()->getClientIp();
        $geoLocation = new GeoLocation();
        $geoLocation->setGeoIpData($contact, $ipAddress);
    }

    protected function setIPWarning()
    {
        $ipFilter = $this->toolBox()->ipFilter();
        $ipFilter->setAttempt($ipFilter->getClientIp());
    }
}

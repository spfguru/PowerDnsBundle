<?php
/**
 * powerdns-api
 * @author   M. Seifert <m.seifert@syseleven.de>
  * @package SysEleven\PowerDnsBundle\Controller
 */
namespace SysEleven\PowerDnsBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use FOS\RestBundle\View\View;
use FOS\RestBundle\Controller\Annotations As Rest;
use SysEleven\PowerDnsBundle\Entity\Domains;
use SysEleven\PowerDnsBundle\Entity\Records;
use SysEleven\PowerDnsBundle\Form\SoaType;
use SysEleven\PowerDnsBundle\Lib\ApiController;
use SysEleven\PowerDnsBundle\Lib\DomainWorkflow;
use SysEleven\PowerDnsBundle\Lib\Exceptions\NotFoundException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use SysEleven\PowerDnsBundle\Lib\Exceptions\ValidationException;
use SysEleven\PowerDnsBundle\Lib\RecordWorkflow;
use SysEleven\PowerDnsBundle\Lib\Soa;
use SysEleven\PowerDnsBundle\Lib\Tools;


/**
 * Class ApiRecordsController
 *
 * @author M. Seifert <m.seifert@syseleven.de>
 * @package SysEleven\PowerDnsBundle\Controller
 * @Route("/domains/{domain}/")
 */
class ApiSoaController extends ApiController
{

    /**
     * Returns the soa record of the given domain.
     *
     * @param Request $request
     * @param         $domain
     *
     * @return \Symfony\Component\HttpFoundation\Response
     * @Rest\Get("soa.{_format}", name="syseleven_powerdns_api_domains_soa")
     */
    public function soaAction(Request $request, $domain)
    {
        try {
            /**
             * @type DomainWorkflow $workflow;
             */
            $workflow = $this->get('syseleven.pdns.workflow.domains');
            /**
             * @type Domains $domainObj
             */
            $domainObj = $workflow->get($domain);

            if (!$soa = $domainObj->getSoa()) {
                $data = array('status' => 'error', 'errors' => array('soa' => 'Not found'));
            } else {
                $data = array('status' => 'success', 'data' => $soa);
            }

        } catch (NotFoundException $nf) {
            $data = array('status' => 'error', 'errors' => array('domain' => 'Not found'));
        }

        return $this->returnView($data, 200, array(), array('details'));

    }

    /**
     * Creates a new SOA record for the given domain,
     *
     * @param Request $request
     * @param         $domain
     *
     * @return \Symfony\Component\HttpFoundation\Response
     * @Rest\Post("soa.{_format}", name="syseleven_powerdns_api_domains_soa_create")
     */
    public function soaCreateAction(Request $request, $domain)
    {
        try {
            /**
             * @type DomainWorkflow $workflow;
             */
            $workflow = $this->get('syseleven.pdns.workflow.domains');
            /**
             * @type Domains $domainObj
             */
            $domainObj = $workflow->get($domain);

            if ($soaRecord = $domainObj->getSoa()) {
                $data = array('status' => 'error', 'errors' => array('soa' => 'Already Exists'));

                return $this->returnView($data, 200, array(), array('details'));
            }

            $soa = new Soa();

            $form = $this->createForm(new SoaType(), $soa, array('method' => 'POST'));
            $form->submit($request, false);

            if (0 == $soa->getSerial()) {
                $soa->setSerial(strtotime('now'));
            }

            $recordObj = new Records();
            $recordObj->setName($domainObj->getName());
            $recordObj->setDomain($domainObj);
            $recordObj->setContent($soa);
            $recordObj->setType('SOA');

            /**
             * @type RecordWorkflow $recordWorkflow;
             */
            $recordWorkflow = $this->get('syseleven.pdns.workflow.records');
            $recordObj = $recordWorkflow->create($recordObj);
            $recordWorkflow->createHistory($recordObj);

            return $this->redirect($this->generateUrl('syseleven_powerdns_api_domains_soa', array('domain' => $domainObj->getId(),'_format' => $request->getRequestFormat())));

        } catch (NotFoundException $nf) {
            $data = array('status' => 'error', 'errors' => array('domain' => 'Not found'));
        } catch (ValidationException $ve) {
            $data = array('status' => 'error', 'errors' => Tools::prepareSymfonyErrorArray($ve->getErrors()));
        }

        return $this->returnView($data, 200, array(), array('details'));
    }

    /**
     * @param Request $request
     * @param         $domain
     *
     * @return \FOS\RestBundle\View\View|\Symfony\Component\HttpFoundation\Response
     * @Rest\Put("soa.{_format}", name="syseleven_powerdns_api_domains_soa_update")
     */
    public function soaUpdateAction(Request $request, $domain)
    {
        try {
            /**
             * @type DomainWorkflow $workflow;
             */
            $workflow = $this->get('syseleven.pdns.workflow.domains');
            /**
             * @type Domains $domainObj
             */
            $domainObj = $workflow->get($domain);

            if (!$recordObj = $domainObj->getSoa()) {
                $data = array('status' => 'error', 'errors' => array('soa' => 'Not found'));

                return $this->returnView($data, 200, array());
            }

            /**
             * @type Soa $soa
             */
            $soa = $recordObj->getContent();
            $oldSerial = $soa->getSerial();

            $form = $this->createForm(new SoaType(), $soa, array('method' => 'PUT'));
            $form->submit($request, false);

            if ($oldSerial == $soa->getSerial()) {
                $soa->setSerial(strtotime('now'));
            }

            /**
             * @type RecordWorkflow $recordWorkflow;
             */
            $recordWorkflow = $this->get('syseleven.pdns.workflow.records');
            $recordObj = $recordWorkflow->update($recordObj);
            $recordWorkflow->createHistory($recordObj);

            return $this->redirect($this->generateUrl('syseleven_powerdns_api_domains_soa', array('domain' => $domainObj->getId(),'_format' => $request->getRequestFormat())));

        } catch (NotFoundException $nf) {
            $data = array('status' => 'error', 'errors' => array('domain' => 'Not found'));
        } catch (ValidationException $ve) {
            $data = array('status' => 'error', 'errors' => Tools::prepareSymfonyErrorArray($ve->getErrors()));
        }

        return $this->returnView($data, 200, array(), array('details'));
    }

    /**
     * @param $domain
     *
     * @return \FOS\RestBundle\View\View|\Symfony\Component\HttpFoundation\Response
     * @Rest\Delete("soa.{_format}", name="syseleven_powerdns_api_domains_soa_delete")
     */
    public function soaDeleteAction($domain)
    {
        try {
            /**
             * @type DomainWorkflow $workflow;
             */
            $workflow = $this->get('syseleven.pdns.workflow.domains');
            /**
             * @type Domains $domainObj
             */
            $domainObj = $workflow->get($domain);

            if (!$recordObj = $domainObj->getSoa()) {
                $data = array('status' => 'error', 'errors' => array('soa' => 'Not found'));

                return $this->returnView($data, 200, array());
            }

            /**
             * @type RecordWorkflow $recordWorkflow;
             */
            $recordWorkflow = $this->get('syseleven.pdns.workflow.records');
            $recordWorkflow->createHistory($recordObj, 'DELETE');
            $recordWorkflow->delete($recordObj);

            $data = array('status' => 'success', 'data' => array('soa' => 'deleted'));

        } catch (NotFoundException $nf) {
            $data = array('status' => 'error', 'errors' => array('domain' => 'Not found'));
        }

        return $this->returnView($data, 200, array(), array('details'));
    }

}
 
<?php

namespace Statusengine2Module\Model\Table;

use App\Lib\Interfaces\NotificationServicesTableInterface;
use App\Lib\Traits\PaginationAndScrollIndexTrait;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;
use itnovum\openITCOCKPIT\Core\ServiceNotificationConditions;
use itnovum\openITCOCKPIT\Database\PaginateOMat;

/**
 * NotificationServices Model
 *
 * @link http://nagios.sourceforge.net/docs/ndoutils/NDOUtils_DB_Model.pdf
 *
 * @property \Statusengine2Module\Model\Table\ObjectsTable|\Cake\ORM\Association\BelongsTo $Objects
 *
 * @method \Statusengine2Module\Model\Entity\NotificationHost get($primaryKey, $options = [])
 * @method \Statusengine2Module\Model\Entity\NotificationHost newEntity($data = null, array $options = [])
 * @method \Statusengine2Module\Model\Entity\NotificationHost[] newEntities(array $data, array $options = [])
 * @method \Statusengine2Module\Model\Entity\NotificationHost|bool save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \Statusengine2Module\Model\Entity\NotificationHost saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \Statusengine2Module\Model\Entity\NotificationHost patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \Statusengine2Module\Model\Entity\NotificationHost[] patchEntities($entities, array $data, array $options = [])
 * @method \Statusengine2Module\Model\Entity\NotificationHost findOrCreate($search, callable $callback = null, $options = [])
 *
 */
class NotificationServicesTable extends Table implements NotificationServicesTableInterface {

    /*****************************************************/
    /*                         !!!                       */
    /*           If you add a method to this table       */
    /*   define it in the implemented interface first!   */
    /*                         !!!                       */
    /*****************************************************/

    use PaginationAndScrollIndexTrait;

    /**
     * Initialize method
     *
     * @param array $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config) {
        parent::initialize($config);

        $this->setTable('nagios_notifications');
        $this->setDisplayField('notification_id');
        $this->setPrimaryKey(['notification_id', 'start_time']);

        $this->belongsTo('Objects', [
            'foreignKey' => 'object_id',
            'joinType'   => 'INNER',
            'className'  => 'Statusengine2Module.Objects'
        ]);
    }

    /**
     * Default validation rules.
     *
     * @param \Cake\Validation\Validator $validator Validator instance.
     * @return \Cake\Validation\Validator
     */
    public function validationDefault(Validator $validator) {
        //Readonly table
        return $validator;
    }

    /**
     * Returns a rules checker object that will be used for validating
     * application integrity.
     *
     * @param \Cake\ORM\RulesChecker $rules The rules object to be modified.
     * @return \Cake\ORM\RulesChecker
     */
    public function buildRules(RulesChecker $rules) {
        //Readonly table
        return $rules;
    }

    /**
     * @param ServiceNotificationConditions $ServiceNotificationConditions
     * @param PaginateOMat|null $PaginateOMat
     * @return array
     */
    public function getNotifications(ServiceNotificationConditions $ServiceNotificationConditions, $PaginateOMat = null) {
        $query = $this->find();
        $query->select([
            'NotificationServices.object_id',
            'NotificationServices.notification_type',
            'NotificationServices.start_time',
            'NotificationServices.state',
            'NotificationServices.output',

            'Hosts.id',
            'Hosts.uuid',
            'Hosts.name',

            'Services.id',
            'Services.uuid',
            'Services.name',

            'Servicetemplates.id',
            'Servicetemplates.uuid',
            'Servicetemplates.name',

            'Contactnotifications.notification_id',
            'Contactnotifications.contact_object_id',
            'Contactnotifications.start_time',

            'Contacts.id',
            'Contacts.uuid',
            'Contacts.name',

            'Commands.id',
            'Commands.uuid',
            'Commands.name',

            'HostsToContainers.container_id',

            'servicename' => $query->newExpr('IF(Services.name IS NULL, Servicetemplates.name, Services.name)'),
        ])
            ->innerJoin(
                ['Objects' => 'nagios_objects'],
                ['Objects.object_id = NotificationServices.object_id']
            )
            ->innerJoin(
                ['Hosts' => 'hosts'],
                ['Objects.name1 = Hosts.uuid']
            )
            ->innerJoin(
                ['Services' => 'services'],
                ['Objects.name2 = Services.uuid']
            )
            ->innerJoin(
                ['Servicetemplates' => 'servicetemplates'],
                ['Services.servicetemplate_id = Servicetemplates.id']
            )
            ->innerJoin(
                ['Contactnotifications' => 'nagios_contactnotifications'],
                ['NotificationServices.notification_id = Contactnotifications.notification_id']
            )
            ->innerJoin(
                ['ContactObjects' => 'nagios_objects'],
                ['Contactnotifications.contact_object_id = ContactObjects.object_id']
            )
            ->innerJoin(
                ['Contacts' => 'contacts'],
                ['ContactObjects.name1 = Contacts.uuid']
            )
            ->innerJoin(
                ['Contactnotificationmethods' => 'nagios_contactnotificationmethods'],
                ['Contactnotificationmethods.contactnotification_id = Contactnotifications.contactnotification_id']
            )
            ->innerJoin(
                ['CommandObjects' => 'nagios_objects'],
                ['Contactnotificationmethods.command_object_id = CommandObjects.object_id']
            )
            ->innerJoin(
                ['Commands' => 'commands'],
                ['CommandObjects.name1 = Commands.uuid']
            )
            ->leftJoin(
                ['HostsToContainers' => 'hosts_to_containers'],
                ['HostsToContainers.host_id = Hosts.id']
            )
            ->where([
                'NotificationServices.start_time >'        => date('Y-m-d H:i:s', $ServiceNotificationConditions->getFrom()),
                'NotificationServices.start_time <'        => date('Y-m-d H:i:s', $ServiceNotificationConditions->getTo()),
                'NotificationServices.notification_type'   => 1,
                'NotificationServices.contacts_notified >' => 0
            ])
            ->order($ServiceNotificationConditions->getOrder())
            ->group(['Contactnotifications.contactnotification_id']);


        if ($ServiceNotificationConditions->getServiceUuid()) {
            $query->andWhere([
                'Objects.name2' => $ServiceNotificationConditions->getServiceUuid()
            ]);
        }

        if ($ServiceNotificationConditions->hasContainerIds()) {
            $query->andWhere([
                'HostsToContainers.container_id IN' => $ServiceNotificationConditions->getContainerIds()
            ]);
        }

        if (!empty($ServiceNotificationConditions->getStates())) {
            $query->andWhere([
                'NotificationServices.state IN' => $ServiceNotificationConditions->getStates()
            ]);
        }

        if ($ServiceNotificationConditions->hasConditions()) {

            $where = $ServiceNotificationConditions->getConditions();
            $having = null;
            if (isset($where['servicename LIKE'])) {
                $having = [
                    'servicename LIKE' => $where['servicename LIKE']
                ];
                unset($where['servicename LIKE']);
            }

            if (!empty($where))
                $query->andWhere($where);

            if (!empty($having)) {
                $query->having($having);
            }
        }

        if ($PaginateOMat === null) {
            //Just execute query
            $result = $this->emptyArrayIfNull($query->toArray());
        } else {
            if ($PaginateOMat->useScroll()) {
                $result = $this->scrollCake4($query, $PaginateOMat->getHandler());
            } else {
                $result = $this->paginateCake4($query, $PaginateOMat->getHandler());
            }
        }

        return $result;
    }
}
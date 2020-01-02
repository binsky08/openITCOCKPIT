<?php
declare(strict_types=1);

namespace MapModule\Model\Table;

use Cake\Datasource\EntityInterface;
use Cake\ORM\Association\BelongsTo;
use Cake\ORM\Behavior\TimestampBehavior;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;
use MapModule\Model\Entity\Mapsummaryitem;

/**
 * Mapsummaryitems Model
 *
 * @property MapsTable&BelongsTo $Maps
 *
 * @method Mapsummaryitem get($primaryKey, $options = [])
 * @method Mapsummaryitem newEntity($data = null, array $options = [])
 * @method Mapsummaryitem[] newEntities(array $data, array $options = [])
 * @method Mapsummaryitem|false save(EntityInterface $entity, $options = [])
 * @method Mapsummaryitem saveOrFail(EntityInterface $entity, $options = [])
 * @method Mapsummaryitem patchEntity(EntityInterface $entity, array $data, array $options = [])
 * @method Mapsummaryitem[] patchEntities($entities, array $data, array $options = [])
 * @method Mapsummaryitem findOrCreate($search, callable $callback = null, $options = [])
 *
 * @mixin TimestampBehavior
 */
class MapsummaryitemsTable extends Table {
    /**
     * Initialize method
     *
     * @param array $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config): void {
        parent::initialize($config);

        $this->setTable('mapsummaryitems');
        $this->setDisplayField('id');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');

        $this->belongsTo('Maps', [
            'foreignKey' => 'map_id',
            'joinType'   => 'INNER',
            'className'  => 'MapModule.Maps',
        ]);
    }

    /**
     * Default validation rules.
     *
     * @param Validator $validator Validator instance.
     * @return Validator
     */
    public function validationDefault(Validator $validator): Validator {
        $validator
            ->integer('id')
            ->allowEmptyString('id', null, 'create');

        $validator
            ->integer('x')
            ->notEmptyString('x');

        $validator
            ->integer('y')
            ->notEmptyString('y');

        $validator
            ->integer('size_x')
            ->notEmptyString('size_x');

        $validator
            ->integer('size_y')
            ->notEmptyString('size_y');

        $validator
            ->integer('limit')
            ->allowEmptyString('limit');

        $validator
            ->scalar('type')
            ->maxLength('type', 20)
            ->requirePresence('type', 'create')
            ->notEmptyString('type');

        $validator
            ->integer('z_index')
            ->notEmptyString('z_index');

        $validator
            ->integer('show_label')
            ->notEmptyString('show_label');

        $validator
            ->integer('label_possition')
            ->notEmptyString('label_possition');

        return $validator;
    }

    /**
     * Returns a rules checker object that will be used for validating
     * application integrity.
     *
     * @param RulesChecker $rules The rules object to be modified.
     * @return RulesChecker
     */
    public function buildRules(RulesChecker $rules): RulesChecker {
        $rules->add($rules->existsIn(['map_id'], 'Maps'));

        return $rules;
    }
}
<?php $this->breadcrumbs = array(
	'Rights'=>array('/rights/main'),
	Yii::t('RightsModule.tr', 'Assignments'),
); ?>

<div class="rights">

	<?php $this->renderPartial('/_menu'); ?>

	<?php $this->renderPartial('/_flash'); ?>

	<div id="rightsAssignments">

		<h2><?php echo Yii::t('RightsModule.tr', 'Assignments'); ?></h2>

		<?php if( count($users)>0 ): ?>

			<table class="rightsTable assignmentsTable" border="0" cellpadding="0" cellspacing="0">

				<thead>

					<tr>

						<th class="usernameColumnHeading"><?php echo Yii::t('RightsModule.tr', 'Username'); ?></th>

						<th class="assignmentColumnHeading"><?php echo Yii::t('RightsModule.tr', 'Assignments'); ?></th>

					</tr>

				</thead>

				<tbody>

					<?php foreach( $users as $id=>$user ): ?>

						<tr class="<?php echo ($i++ % 2)===0 ? 'odd' : 'even'; ?>">

							<td><?php echo CHtml::link($user->$username, array('assignment/user', 'id'=>$id)); ?></td>

							<td class="assignmentColumn"><?php echo isset($assignments[ $id ])===true ? implode(', ', $assignments[ $id ]) : ''; ?></td>

						</tr>

					<?php endforeach; ?>

				</tbody>

			</table>

			<?php $this->widget('CLinkPager', array('pages'=>$pages)); ?>

		<?php else: ?>

			<p><?php echo Yii::t('RightsModule.tr', 'No users found.'); ?></p>

		<?php endif; ?>

	</div>

</div>

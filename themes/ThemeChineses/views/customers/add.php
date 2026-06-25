<?php (defined('BASEPATH')) OR exit('No direct script access allowed'); ?>

<section class="content">
	<div class="row">
		<div class="col-xs-12">
			<div class="box box-primary">
				<div class="box-header">
					<h3 class="box-title"><?= lang('enter_info'); ?></h3>
				</div>
				<div class="box-body">
					<?php echo form_open("customers/add");?>

					<div class="col-md-6">
						<div class="form-group">
							<label class="control-label" for="code"><?= $this->lang->line("name"); ?></label>
							<?= form_input('name', set_value('name'), 'class="form-control input-sm" id="name"'); ?>
						</div>
                        <div class="form-group">
                            <label class="control-label" for="code">Nombre Comercial</label>
                            <?= form_input('business_name', set_value('business_name'), 'class="form-control input-sm" id="business_name"'); ?>
                        </div>

						<div class="form-group">
							<label class="control-label" for="email_address"><?= $this->lang->line("email_address"); ?></label>
							<?= form_input('email', set_value('email'), 'class="form-control input-sm" id="email_address"'); ?>
						</div>

						<div class="form-group">
							<label class="control-label" for="phone"><?= $this->lang->line("phone"); ?></label>
							<?= form_input('phone', set_value('phone'), 'class="form-control input-sm" id="phone"');?>
						</div>

                        <div class="form-group">
                            <label class="control-label" for="cf1"><?= $this->lang->line("ccf1"); ?></label>
                            <select name="cf1" class="form-control input-sm selct2" id="cf1" >
                                <option <? @$customer->cf1 == '01' ? ' selected selected="selected"' : '' ?> value="01">Cedula de Identidad</option>
                                <option <? @$customer->cf1 == '02' ? ' selected selected="selected"' : '' ?> value="02">Cedula Juridica</option>
                                <option <? @$customer->cf1 == '03' ? ' selected selected="selected"' : '' ?> value="03">DIMEX</option>
                                <option <? @$customer->cf1 == '04' ? ' selected selected="selected"' : '' ?> value="04">NITE</option>
                                <option <? @$customer->cf1 == '05' ? ' selected selected="selected"' : '' ?> value="05">Passaporte</option>
                            </select>
                            <!--							--><?//= form_input('cf1', set_value('cf1'), 'class="form-control input-sm" id="cf1"'); ?>
                        </div>

                        <div class="form-group">
                            <label class="control-label" for="cf2"><?= $this->lang->line("ccf2"); ?></label>
                            <?= form_input('cf2', set_value('cf2'), 'class="form-control input-sm" id="cf2"');?>
                        </div>

                        <? if($Settings->enable_credit == 1) { ?>
                            <div class="form-group">
                                <label class="control-label" for="limitcredit"><?= $this->lang->line("limitcredit"); ?></label>
                                <?= form_input('limitcredit', set_value('limitcredit', @$customer->limitcredit), 'class="form-control input-sm" id="cf2"');?>
                            </div>
                        <? } ?>

						<div class="form-group">
							<?php echo form_submit('add_customer', $this->lang->line("add_customer"), 'class="btn btn-primary"');?>
						</div>
					</div>
					<?php echo form_close();?>
				</div>
			</div>
		</div>
	</div>
</section>

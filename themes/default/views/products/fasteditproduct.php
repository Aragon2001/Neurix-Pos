
<section class="content">
    <div class="row">
        <div class="col-12">
            <div class="box box-primary">
                <div class="box-header">
                    <!-------------------------->
                    <!--Aqui empieza el codigo-->    
                    <!-------------------------->

                    <div class="row">
                        <div class="col-lg-12">
                            <div class="input-group">
                                <input id="scanner_input" class="form-control" placeholder="Click the button to scan an EAN..." type="text" /> 
                                <span class="input-group-btn"> 
                                    <button class="btn btn-default" onclick="$('#scanner_input').focus();" type="button">
                                        <i class="fa fa-barcode"></i>
                                    </button> 
                                </span>

                            </div><!-- /input-group -->
                        </div><!-- /.col-lg-6 -->
                    </div><!-- /.row -->

                    
                    <div class="row">
                            <div class="col-md-12">
                                <div class="table-responsive">
                                     <?= form_open_multipart("products/postFastedit/", 'id="fasteditPOST"'); ?>
                                    <?php if($mmm){ ?>
                                    <input type="hidden" name='ajuste' value='1' />
                                    <?php } ?> 
                                    <table id="poTableFast" class="table table-striped table-bordered">
                                        <thead>
                                        <tr class="active">
                                            <th><?= lang('product'); ?></th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        <tr>
                                            <td><?= lang('add_product_by_searching_above_field'); ?></td>
                                        </tr>
                                        </tbody>
                                        <tfoot>
                                        
                                        </tfoot>
                                    </table>
                                    <?= form_close(); ?>
                                </div>
                            </div>
                        </div>
                    
                    

                    <!--------------------------->
                    <!--Aqui finaliza el codigo-->    
                    <!--------------------------->

                </div>
                <div class="box-body">
                </div>
            </div>
        </div>
    </div>
</section>

<style>
    #interactive.viewport {position: relative; width: 100%; height: auto; overflow: hidden; text-align: center;}
    #interactive.viewport > canvas, #interactive.viewport > video {max-width: 100%;width: 100%;}
    canvas.drawing, canvas.drawingBuffer {position: absolute; left: 0; top: 0;}
</style>

<script type="text/javascript">
    var spoitemsfast = {};
    var count = 1;
    var enable_fractions = "<?= $Settings->enable_fractions ?>";
    if (localStorage.getItem('remove_spofast')) {
        if (localStorage.getItem('spoitemsfast')) {
            localStorage.removeItem('spoitemsfast');
        }
        localStorage.removeItem('remove_spofast');
    }
</script>
<?php if($mmm){ ?>
<script src="<?= $assets ?>dist/js/ajuste.min.js" type="text/javascript"></script>
<?php }else{ ?> 
<script src="<?= $assets ?>dist/js/fastedit.min.js" type="text/javascript"></script>
<?php } ?> 
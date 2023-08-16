public function lab_json()
{
    $start_date = ($this->input->get('start_date') != '') ? $this->input->get('start_date') : date('d-m-Y');
    $end_date   = ($this->input->get('end_date') != '') ? $this->input->get('end_date') : date('d-m-Y');

    $reportData = (object)array(
        'report_option' => $this->input->get('report_option'), 
        'operator_id' => $this->input->get('operator_id'), 
        'patient_id' => $this->input->get('patient_id'), 
        'pc_id' => $this->input->get('pc_id'), 
        'start_date' => $start_date,
        'end_date' => $end_date,
    );  

    $data['invoice'] = $this->report_model->lab_reports($reportData);
    $data['content'] = $this->load->view('account_manager/report_lab_json', $data, true);

    // Send the JSON response
    $this->output
        ->set_content_type('application/json')
        ->set_output(json_encode($data));
}

<div class="col-sm-12">
    <div class="panel panel-default">
        <div class="panel-body">
            <form class="form-inline" action="<?php echo base_url('account_manager/report/lab') ?>">

                <ol class="breadcrumb">
                    <li><a href="#"><i class="pe-7s-home"></i> <?php echo display('home') ?></a></li>
                    <li><a href="#"><i class="fa fa-folder-open-o"></i> <?php echo display('lab_report') ?></a></li>
                    <li class="active"><?php echo display('lab_invoice_report') ?></li>
                </ol>

                <div class="form-group col-md-3">
                    <?php
                    $ReportOption = array(
                        '1' => display('all'),
                        '2' => display('patient_wise'),
                        '3' => display('operator_wise'),
                        '4' => display('doctor_wise'),
                        '7' => 'PC Wise',
                    );
                    echo form_dropdown('report_option', $ReportOption, $date->report_option, 'class="form-control" id="reportoption"  onchange="get_patient_type(this.value);"');
                    ?>
                </div>

                <div class="form-group hide col-md-3" id="AccountWise">
                    <label class="sr-only" for="operator_id"><?php echo display('operator_id') ?></label>
                    <?php echo form_dropdown('operator_id', $operator_list, $date->operator_id, 'id="operator_id" class="form-control"') ?>
                </div>

                <div class="form-group hide col-md-3" id="PatientWise">
                    <label class="sr-only" for="patient_id"><?php echo display('patient_id') ?></label>
                    <input type="text" name="patient_id" class="form-control" id="patient_id" placeholder="<?php echo display('patient_id') ?>" value="<?php echo $date->patient_id ?>">
                </div>

                <div class="form-group hide col-md-3" id="DoctorWise">
                    <label class="sr-only" for="doctor_id"><?php echo display('doctor_id') ?></label>
                    <?php echo form_dropdown('doctor_id', $doctor_list, $date->doctor_id, 'class="form-control"') ?>
                </div>

                <div class="form-group hide col-md-3" id="PcWise">
                    <label class="sr-only" for="doctor_id">PC Wise</label>
                    <select name="pc_id" id="pc_id" class="form-control select2-hidden-accessible" tabindex="-1" aria-hidden="true">
                        <option value="0">-- Select PC --</option>
                    </select>
                </div>

                <div class="form-group col-md-2">
                    <label class="sr-only" for="start_date"><?php echo display('start_date') ?></label>
                    <input type="text" name="start_date" class="form-control datepicker" id="start_date" placeholder="<?php echo display('start_date') ?>" value="<?php echo $date->start_date ?>">
                </div>

                <div class="form-group col-md-2">
                    <label class="sr-only" for="end_date"><?php echo display('end_date') ?></label>
                    <input type="text" name="end_date" class="form-control datepicker" id="end_date" placeholder="<?php echo display('end_date') ?>" value="<?php echo $date->end_date ?>">
                </div>

                <button type="button" class="btn btn-success col-md-1" onclick="get_lab_report();"><?php echo display('filter') ?></button>
                <button type="button" onclick="PrintMe('printMe')" class="btn btn-danger col-md-1"><i class="fa fa-print"></i></button>
            </form>
        </div>
    </div>
</div>

<script>
    function get_lab_report() {
        var reportoption = $('#reportoption').val();
        var operator_id = $('#operator_id').val();
        var patient_id = $('#patient_id').val();
        var doctor_id = $('#doctor_id').val();
        var pc_id = $('#pc_id').val();
        var start_date = $('#start_date').val();
        var end_date = $('#end_date').val();
        $.ajax({
            type: "POST",
            url: '<?php echo base_url(); ?>account_manager/report/lab',
            data: {
                'reportoption': reportoption,
                'operator_id': operator_id,
                'patient_id': patient_id,
                'doctor_id': doctor_id,
                'pc_id': pc_id,
                'start_date': start_date,
                'end_date': end_date,
            },
            success: function (html_data) {
                if (html_data != '') {
                    $('#result').html(html_data);
                }
            }
        });
    }
</script>


public function readPaidInvoice($limit = 10, $offset = 0)
{
    $condition = '';

    if ($offset != 0) {
        $limitCondition = "LIMIT " . $offset . " , " . $limit;
    } else {
        $limitCondition = "LIMIT " . $limit;
    }

    return $this->db->query("SELECT j0.*, CONCAT(patient.firstname,' ',patient.lastname) AS patientName
                            FROM (
                                SELECT DISTINCT l.id AS db_invoice_id, l.* FROM lab_due_invoice AS ld
                                LEFT OUTER JOIN lab_invoice AS l ON l.id = ld.invoice_id
                                WHERE l.due_amount = 0
                            ) as j0
                            LEFT OUTER JOIN client ON client.id = j0.patient_id
                            LEFT OUTER JOIN user AS patient ON patient.user_id = client.client_id
                            " . $limitCondition)->result();
}

public function paid_list($invoice_type = 'IPD')
{
    $data['title'] = "Due Paid List";
    #-------------------------------#
    #Pagination Start

    $this->load->library('pagination'); // Load the Pagination library

    $config["base_url"] = base_url('account_manager/lab_due_invoice/paid_list/'.$invoice_type);
    $config['suffix'] = '?' . http_build_query($_GET, '', "&");
    $config['first_url'] = $config['base_url'] . $config['suffix'];
    $config["total_rows"] = $this->lab_due_invoice_model->getTotalDuePaidRow();
    $config["per_page"] = 10;
    $config["uri_segment"] = 6; // Update the URL segment to 6 to reflect the correct segment for pagination offset
    $config["num_links"] = 5;
    $config["last_link"] = "Last";
    $config["first_link"] = "First";
    $config['next_link'] = 'Next';
    $config['prev_link'] = 'Prev';
    $config['full_tag_open'] = "<ul class='pagination pagination-xs'>";
    $config['full_tag_close'] = "</ul>";
    $config['num_tag_open'] = '<li>';
    $config['num_tag_close'] = '</li>';
    $config['cur_tag_open'] = "<li class='disabled'><li class='active'><a href='#'>";
    $config['cur_tag_close'] = "<span class='sr-only'></span></a></li>";
    $config['next_tag_open'] = "<li>";
    $config['next_tag_close'] = "</li>";
    $config['prev_tag_open'] = "<li>";
    $config['prev_tag_close'] = "</li>";
    $config['first_tag_open'] = "<li>";
    $config['first_tag_close'] = "</li>";
    $config['last_tag_open'] = "<li>";
    $config['last_tag_close'] = "</li>";

    $this->pagination->initialize($config);
    $page = ($this->uri->segment(6)) ? $this->uri->segment(6) : 0; // Update the segment number to 6

    # pagination ends
    #
    $data['offset'] = $page;
    $data['invoice_type'] = $invoice_type; 
    $data['lab_due_paid_invoice'] = $this->lab_due_invoice_model->readPaidInvoice($config["per_page"], $page, $invoice_type);
    $data["links"] = $this->pagination->create_links();

    # Pagination end

    $data['content'] = $this->load->view('lab_manager/lab_due_invoice/lab_due_paid_invoice', $data, true);

    $this->load->view('layout/main_wrapper', $data);
}

<table width="100%" class="table table-striped table-bordered table-hover">
    <thead>
        <tr>
            <th><?php echo display('serial') ?></th>
            <th><?php echo display('date') ?></th>
            <th><?php echo display('patient_name') ?></th>
            <th><?php echo display('invoice') ?></th>
            <th><?php echo display('total') ?></th>
            <th><?php echo display('vat') ?></th>
            <th><?php echo display('discount') ?></th>
            <th><?php echo display('paid') ?></th>
            <th><?php echo display('due') ?></th>
            <th width="80"><?php echo display('action') ?></th>
        </tr>
    </thead>
    <tbody>
        <?php 
        if (!empty($lab_due_paid_invoice)) {
            $sl = $offset + 1;
            foreach ($lab_due_paid_invoice as $value) {
        ?>
                <tr class="<?php echo ($sl & 1) ? "odd gradeX" : "even gradeC" ?>">
                    <td><?php echo $sl; ?></td>
                    <td><?= nice_date($value->receipt_date) ?></td>
                    <td><?php echo $value->patientName; ?></td>
                    <td><?php echo $value->invoice_id; ?></td>
                    <td><?php echo sprintf('%0.2f', $value->total_amount); ?></td>
                    <td><?php echo sprintf('%0.2f', $value->total_tax); ?></td>
                    <td><?php echo sprintf('%0.2f', $value->total_discount); ?></td> 
                    <td><?php echo sprintf('%0.2f', $value->paid_amount); ?></td>
                    <td><?php echo sprintf('%0.2f', $value->due_amount); ?></td>

                    <td class="center">
                        <a href="<?php echo base_url("lab_manager/invoice/details/$value->id") ?>" class="btn btn-xs btn-primary"><i class="fa fa-eye"></i></a>  
                    </td>
                </tr>
        <?php 
                $sl++;
            }
        } else {
            // Display a message when there are no records.
            echo '<tr><td colspan="10" class="text-center">No records found.</td></tr>';
        }
        ?>
    </tbody>
</table>

<div class="pagination">
    <?php echo $links ?>
</div>


function setInvoiceidDetails(invoice_id) {
    $.ajax({
        type: "POST",
        url: '<?php echo base_url("account_manager/report/payment_labinvoice_json") ?>',
        data: {
            'invoice_id': invoice_id
        },
        dataType: 'json',
        success: function(data) {
            if (data.result.length > 0) {
                $("#mobile").val(data.result[0].mobile);
            } else {
                // Handle the case where no data is returned for the given invoice ID
            }
        },
        error: function(xhr, status, error) {
            console.error("AJAX Error:", error);
        }
    });
}

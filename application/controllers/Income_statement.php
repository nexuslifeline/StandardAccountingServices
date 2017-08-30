<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Income_statement extends CORE_Controller
{

    function __construct() {
        parent::__construct('');
        $this->validate_session();
        $this->load->model(array(
                'Account_class_model',
                'Journal_info_model',
                'Journal_account_model',
                'Users_model',
                'Departments_model',
                'Company_model'
            )
        );
        $this->load->library('excel');
    }

    public function index() {
        $this->Users_model->validate();
        $data['_def_css_files'] = $this->load->view('template/assets/css_files', '', true);
        $data['_def_js_files'] = $this->load->view('template/assets/js_files', '', true);
        $data['_switcher_settings'] = $this->load->view('template/elements/switcher', '', true);
        $data['_side_bar_navigation'] = $this->load->view('template/elements/side_bar_navigation', '', true);
        $data['_top_navigation'] = $this->load->view('template/elements/top_navigation', '', true);
        $data['title'] = 'Income Statement';

        $data['departments']=$this->Departments_model->get_list('is_deleted=FALSE');
        $data['income_accounts']=$this->Journal_info_model->get_account_balance(4);
        $data['expense_accounts']=$this->Journal_info_model->get_account_balance(5);
        (in_array('9-2',$this->session->user_rights)? 
        $this->load->view('income_statement_view', $data)
        :redirect(base_url('dashboard')));
        
    }

    function transaction($txn)
    {
        switch($txn)
        {
            case 'export-excel':
                $m_journal = $this->Journal_info_model;
                $m_company=$this->Company_model;

                $company_info=$m_company->get_list();
                $start=$this->input->get('start',TRUE);
                $end=$this->input->get('end',TRUE);

                $income_accounts = $m_journal->get_account_balance(4);
                $expense_accounts = $m_journal->get_account_balance(5);

                $excel=$this->excel;

                $excel->setActiveSheetIndex(0);
                $excel->getActiveSheet()->getColumnDimensionByColumn('B')->setWidth('500');
                $excel->getActiveSheet()->getColumnDimensionByColumn('C')->setWidth('100');
                $excel->getActiveSheet()->getColumnDimensionByColumn('D')->setWidth('100');

                $excel->getActiveSheet()->setTitle('Income Statement');

                $excel->getActiveSheet()->setCellValue('A1',$company_info[0]->company_name)
                                        ->setCellValue('A2',$company_info[0]->company_address)
                                        ->setCellValue('A3',$company_info[0]->email_address)
                                        ->setCellValue('A4',$company_info[0]->mobile_no);

                $excel->getActiveSheet()->getStyle('A1')->getFont()->setBold(TRUE);

                $excel->getActiveSheet()->setCellValue('A6','INCOME STATEMENT')
                                        ->setCellValue('A7',$start.' to '.$end);

                $excel->getActiveSheet()->getStyle('A6')->getFont()->setBold(TRUE);
                $excel->getActiveSheet()->getStyle('B9:D9')->getFont()->setBold(TRUE);
                $excel->getActiveSheet()->getStyle('A7')->getFont()->setItalic(TRUE);

                $excel->getActiveSheet()->setCellValue('A9', 'INCOME')
                                        ->getStyle('A9')->getFont()
                                        ->setItalic(TRUE)
                                        ->setBold(TRUE);
                $i = 9;
                $income_total=0;
                $total_net = 0;
                foreach($income_accounts as $income_account)
                {
                    $i++;

                    $excel->getActiveSheet()->setCellValue('A'.$i,$income_account->account_title);
                    $excel->getActiveSheet()->setCellValue('B'.$i,number_format($income_account->account_balance,2));

                    $income_total+=$income_account->account_balance;

                }

                $i++;
                $excel->getActiveSheet()->setCellValue('A'.$i,'Total Income:')->getStyle('A'.$i)->getFont()
                                        ->setBold(TRUE);
                $excel->getActiveSheet()->setCellValue('B'.$i,number_format($income_total,2))->getStyle('A'.$i)->getFont()
                                        ->setBold(TRUE);

                $i+=2;

                $excel->getActiveSheet()->setCellValue('A'.$i, 'EXPENSES')
                                        ->getStyle('A'.$i)->getFont()
                                        ->setItalic(TRUE)
                                        ->setBold(TRUE);

                $expense_total = 0;
                foreach($expense_accounts as $expense_account)
                {
                    $i++;

                    $excel->getActiveSheet()->setCellValue('A'.$i,$expense_account->account_title);
                    $excel->getActiveSheet()->setCellValue('B'.$i,number_format($expense_account->account_balance,2));

                    $expense_total+=$expense_account->account_balance;
                }

                $i++;
                $excel->getActiveSheet()->setCellValue('A'.$i,'Total Expense:')->getStyle('A'.$i)->getFont()
                                        ->setBold(TRUE);
                $excel->getActiveSheet()->setCellValue('B'.$i,number_format($expense_total,2))->getStyle('B'.$i)->getFont()
                                        ->setBold(TRUE);

                $total_net = $income_total + $expense_total;

                $i++;
                $excel->getActiveSheet()->setCellValue('A'.$i, 'NET INCOME:')->getStyle('A'.$i)->getFont()
                                        ->setBold(TRUE);
                $excel->getActiveSheet()->setCellValue('B'.$i, number_format($total_net,2))->getStyle('B'.$i)->getFont()
                                        ->setBold(TRUE);


                // Redirect output to a client’s web browser (Excel2007)
                header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
                header('Content-Disposition: attachment;filename="Income Statement '.date('Y-m-d',strtotime($end)).'.xlsx"');
                header('Cache-Control: max-age=0');
                // If you're serving to IE 9, then the following may be needed
                header('Cache-Control: max-age=1');

                // If you're serving to IE over SSL, then the following may be needed
                header ('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
                header ('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT'); // always modified
                header ('Cache-Control: cache, must-revalidate'); // HTTP/1.1
                header ('Pragma: public'); // HTTP/1.0

                $objWriter = PHPExcel_IOFactory::createWriter($excel, 'Excel2007');
                $objWriter->save('php://output');
                break;
        }
    }
}

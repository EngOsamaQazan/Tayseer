# تقرير هندسة عكسية — واجهات Yii2 (Backend)

## أرقام ملخصة

- **عدد الشاشات:** 479
- **عدد الفورمز:** 163
- **عدد ملفات الـ views:** 755

---

## أ) جدول الشاشات

| اسم الشاشة في الكود | اسم الشاشة بالعربي | الوظيفة | ملف الـ View |
| --- | --- | --- | --- |
| `modules/accounting/views/accounts-payable/aging-report.php` | تقرير أعمار الذمم الدائنة | واجهة aging-report ضمن accounts payable. | `backend/modules/accounting/views/accounts-payable/aging-report.php` |
| `modules/accounting/views/accounts-payable/create.php` | ذمة دائنة جديدة | إنشاء سجل جديد في accounts payable. | `backend/modules/accounting/views/accounts-payable/create.php` |
| `modules/accounting/views/accounts-payable/index.php` | الذمم الدائنة | عرض وإدارة سجلات accounts payable. | `backend/modules/accounting/views/accounts-payable/index.php` |
| `modules/accounting/views/accounts-payable/update.php` | تعديل الذمة الدائنة # | تعديل سجل موجود في accounts payable. | `backend/modules/accounting/views/accounts-payable/update.php` |
| `modules/accounting/views/accounts-receivable/aging-report.php` | تقرير أعمار الذمم المدينة | واجهة aging-report ضمن accounts receivable. | `backend/modules/accounting/views/accounts-receivable/aging-report.php` |
| `modules/accounting/views/accounts-receivable/create.php` | ذمة مدينة جديدة | إنشاء سجل جديد في accounts receivable. | `backend/modules/accounting/views/accounts-receivable/create.php` |
| `modules/accounting/views/accounts-receivable/index.php` | الذمم المدينة | عرض وإدارة سجلات accounts receivable. | `backend/modules/accounting/views/accounts-receivable/index.php` |
| `modules/accounting/views/accounts-receivable/update.php` | تعديل الذمة المدينة # | تعديل سجل موجود في accounts receivable. | `backend/modules/accounting/views/accounts-receivable/update.php` |
| `modules/accounting/views/ai-insights/index.php` | التحليل الذكي والتوصيات | عرض وإدارة سجلات ai insights. | `backend/modules/accounting/views/ai-insights/index.php` |
| `modules/accounting/views/budget/create.php` | موازنة جديدة | إنشاء سجل جديد في budget. | `backend/modules/accounting/views/budget/create.php` |
| `modules/accounting/views/budget/index.php` | الموازنات | عرض وإدارة سجلات budget. | `backend/modules/accounting/views/budget/index.php` |
| `modules/accounting/views/budget/update.php` | تعديل الموازنة: | تعديل سجل موجود في budget. | `backend/modules/accounting/views/budget/update.php` |
| `modules/accounting/views/budget/variance.php` | تقرير انحراف الموازنة: | واجهة variance ضمن budget. | `backend/modules/accounting/views/budget/variance.php` |
| `modules/accounting/views/budget/view.php` | accounting — budget / view (عرض تفاصيل) (تقديري من الكود) | عرض تفاصيل سجل في budget. | `backend/modules/accounting/views/budget/view.php` |
| `modules/accounting/views/chart-of-accounts/create.php` | إضافة حساب جديد | إنشاء سجل جديد في chart of accounts. | `backend/modules/accounting/views/chart-of-accounts/create.php` |
| `modules/accounting/views/chart-of-accounts/index.php` | شجرة الحسابات | عرض وإدارة سجلات chart of accounts. | `backend/modules/accounting/views/chart-of-accounts/index.php` |
| `modules/accounting/views/chart-of-accounts/tree.php` | شجرة الحسابات - عرض شجري | واجهة tree ضمن chart of accounts. | `backend/modules/accounting/views/chart-of-accounts/tree.php` |
| `modules/accounting/views/chart-of-accounts/update.php` | تعديل: | تعديل سجل موجود في chart of accounts. | `backend/modules/accounting/views/chart-of-accounts/update.php` |
| `modules/accounting/views/cost-center/create.php` | إضافة مركز تكلفة | إنشاء سجل جديد في cost center. | `backend/modules/accounting/views/cost-center/create.php` |
| `modules/accounting/views/cost-center/index.php` | مراكز التكلفة | عرض وإدارة سجلات cost center. | `backend/modules/accounting/views/cost-center/index.php` |
| `modules/accounting/views/cost-center/update.php` | تعديل: | تعديل سجل موجود في cost center. | `backend/modules/accounting/views/cost-center/update.php` |
| `modules/accounting/views/default/index.php` | لوحة تحكم المحاسبة | عرض وإدارة سجلات default. | `backend/modules/accounting/views/default/index.php` |
| `modules/accounting/views/financial-statements/balance-sheet.php` | الميزانية العمومية (المركز المالي) | واجهة balance-sheet ضمن financial statements. | `backend/modules/accounting/views/financial-statements/balance-sheet.php` |
| `modules/accounting/views/financial-statements/cash-flow.php` | قائمة التدفقات النقدية | واجهة cash-flow ضمن financial statements. | `backend/modules/accounting/views/financial-statements/cash-flow.php` |
| `modules/accounting/views/financial-statements/income-statement.php` | قائمة الدخل | واجهة income-statement ضمن financial statements. | `backend/modules/accounting/views/financial-statements/income-statement.php` |
| `modules/accounting/views/financial-statements/trial-balance.php` | ميزان المراجعة | واجهة trial-balance ضمن financial statements. | `backend/modules/accounting/views/financial-statements/trial-balance.php` |
| `modules/accounting/views/fiscal-year/create.php` | إضافة سنة مالية | إنشاء سجل جديد في fiscal year. | `backend/modules/accounting/views/fiscal-year/create.php` |
| `modules/accounting/views/fiscal-year/index.php` | السنوات المالية | عرض وإدارة سجلات fiscal year. | `backend/modules/accounting/views/fiscal-year/index.php` |
| `modules/accounting/views/fiscal-year/update.php` | تعديل: | تعديل سجل موجود في fiscal year. | `backend/modules/accounting/views/fiscal-year/update.php` |
| `modules/accounting/views/fiscal-year/view.php` | accounting — fiscal-year / view (عرض تفاصيل) (تقديري من الكود) | عرض تفاصيل سجل في fiscal year. | `backend/modules/accounting/views/fiscal-year/view.php` |
| `modules/accounting/views/general-ledger/account.php` | دفتر حساب: | واجهة account ضمن general ledger. | `backend/modules/accounting/views/general-ledger/account.php` |
| `modules/accounting/views/general-ledger/index.php` | الأستاذ العام | عرض وإدارة سجلات general ledger. | `backend/modules/accounting/views/general-ledger/index.php` |
| `modules/accounting/views/journal-entry/create.php` | قيد يومية جديد | إنشاء سجل جديد في journal entry. | `backend/modules/accounting/views/journal-entry/create.php` |
| `modules/accounting/views/journal-entry/index.php` | القيود اليومية | عرض وإدارة سجلات journal entry. | `backend/modules/accounting/views/journal-entry/index.php` |
| `modules/accounting/views/journal-entry/update.php` | تعديل القيد: | تعديل سجل موجود في journal entry. | `backend/modules/accounting/views/journal-entry/update.php` |
| `modules/accounting/views/journal-entry/view.php` | قيد رقم | عرض تفاصيل سجل في journal entry. | `backend/modules/accounting/views/journal-entry/view.php` |
| `modules/address/views/address/create.php` | address — address / create (إنشاء سجل جديد) (تقديري من الكود) | إنشاء سجل جديد في address. | `backend/modules/address/views/address/create.php` |
| `modules/address/views/address/index.php` | address — address / index (قائمة / لوحة) (تقديري من الكود) | عرض وإدارة سجلات address. | `backend/modules/address/views/address/index.php` |
| `modules/address/views/address/update.php` | address — address / update (تعديل سجل) (تقديري من الكود) | تعديل سجل موجود في address. | `backend/modules/address/views/address/update.php` |
| `modules/address/views/address/view.php` | address — address / view (عرض تفاصيل) (تقديري من الكود) | عرض تفاصيل سجل في address. | `backend/modules/address/views/address/view.php` |
| `modules/attendance/views/attendance/create.php` | attendance — attendance / create (إنشاء سجل جديد) (تقديري من الكود) | إنشاء سجل جديد في attendance. | `backend/modules/attendance/views/attendance/create.php` |
| `modules/attendance/views/attendance/index.php` | attendance — attendance / index (قائمة / لوحة) (تقديري من الكود) | عرض وإدارة سجلات attendance. | `backend/modules/attendance/views/attendance/index.php` |
| `modules/attendance/views/attendance/update.php` | attendance — attendance / update (تعديل سجل) (تقديري من الكود) | تعديل سجل موجود في attendance. | `backend/modules/attendance/views/attendance/update.php` |
| `modules/attendance/views/attendance/view.php` | attendance — attendance / view (عرض تفاصيل) (تقديري من الكود) | عرض تفاصيل سجل في attendance. | `backend/modules/attendance/views/attendance/view.php` |
| `modules/authAssignment/views/auth-assignment/create.php` | authAssignment — auth-assignment / create (إنشاء سجل جديد) (تقديري من الكود) | إنشاء سجل جديد في auth assignment. | `backend/modules/authAssignment/views/auth-assignment/create.php` |
| `modules/authAssignment/views/auth-assignment/index.php` | authAssignment — auth-assignment / index (قائمة / لوحة) (تقديري من الكود) | عرض وإدارة سجلات auth assignment. | `backend/modules/authAssignment/views/auth-assignment/index.php` |
| `modules/authAssignment/views/auth-assignment/update.php` | authAssignment — auth-assignment / update (تعديل سجل) (تقديري من الكود) | تعديل سجل موجود في auth assignment. | `backend/modules/authAssignment/views/auth-assignment/update.php` |
| `modules/authAssignment/views/auth-assignment/view.php` | authAssignment — auth-assignment / view (عرض تفاصيل) (تقديري من الكود) | عرض تفاصيل سجل في auth assignment. | `backend/modules/authAssignment/views/auth-assignment/view.php` |
| `modules/bancks/views/bancks/create.php` | bancks — bancks / create (إنشاء سجل جديد) (تقديري من الكود) | إنشاء سجل جديد في bancks. | `backend/modules/bancks/views/bancks/create.php` |
| `modules/bancks/views/bancks/index.php` | Bancks (تقديري من الكود) | عرض وإدارة سجلات bancks. | `backend/modules/bancks/views/bancks/index.php` |
| `modules/bancks/views/bancks/update.php` | bancks — bancks / update (تعديل سجل) (تقديري من الكود) | تعديل سجل موجود في bancks. | `backend/modules/bancks/views/bancks/update.php` |
| `modules/bancks/views/bancks/view.php` | bancks — bancks / view (عرض تفاصيل) (تقديري من الكود) | عرض تفاصيل سجل في bancks. | `backend/modules/bancks/views/bancks/view.php` |
| `modules/capitalTransactions/views/capital-transactions/create.php` | إضافة حركة رأس مال | إنشاء سجل جديد في capital transactions. | `backend/modules/capitalTransactions/views/capital-transactions/create.php` |
| `modules/capitalTransactions/views/capital-transactions/index.php` | حركات رأس المال | عرض وإدارة سجلات capital transactions. | `backend/modules/capitalTransactions/views/capital-transactions/index.php` |
| `modules/capitalTransactions/views/capital-transactions/update.php` | تعديل حركة رأس مال # | تعديل سجل موجود في capital transactions. | `backend/modules/capitalTransactions/views/capital-transactions/update.php` |
| `modules/capitalTransactions/views/capital-transactions/view.php` | عرض حركة رأس مال # | عرض تفاصيل سجل في capital transactions. | `backend/modules/capitalTransactions/views/capital-transactions/view.php` |
| `modules/citizen/views/citizen/create.php` | citizen — citizen / create (إنشاء سجل جديد) (تقديري من الكود) | إنشاء سجل جديد في citizen. | `backend/modules/citizen/views/citizen/create.php` |
| `modules/citizen/views/citizen/index.php` | Citizens (تقديري من الكود) | عرض وإدارة سجلات citizen. | `backend/modules/citizen/views/citizen/index.php` |
| `modules/citizen/views/citizen/update.php` | citizen — citizen / update (تعديل سجل) (تقديري من الكود) | تعديل سجل موجود في citizen. | `backend/modules/citizen/views/citizen/update.php` |
| `modules/citizen/views/citizen/view.php` | citizen — citizen / view (عرض تفاصيل) (تقديري من الكود) | عرض تفاصيل سجل في citizen. | `backend/modules/citizen/views/citizen/view.php` |
| `modules/city/views/city/create.php` | city — city / create (إنشاء سجل جديد) (تقديري من الكود) | إنشاء سجل جديد في city. | `backend/modules/city/views/city/create.php` |
| `modules/city/views/city/index.php` | Cities (تقديري من الكود) | عرض وإدارة سجلات city. | `backend/modules/city/views/city/index.php` |
| `modules/city/views/city/update.php` | city — city / update (تعديل سجل) (تقديري من الكود) | تعديل سجل موجود في city. | `backend/modules/city/views/city/update.php` |
| `modules/city/views/city/view.php` | city — city / view (عرض تفاصيل) (تقديري من الكود) | عرض تفاصيل سجل في city. | `backend/modules/city/views/city/view.php` |
| `modules/collection/views/collection/create.php` | collection — collection / create (إنشاء سجل جديد) (تقديري من الكود) | إنشاء سجل جديد في collection. | `backend/modules/collection/views/collection/create.php` |
| `modules/collection/views/collection/index.php` | collection — collection / index (قائمة / لوحة) (تقديري من الكود) | عرض وإدارة سجلات collection. | `backend/modules/collection/views/collection/index.php` |
| `modules/collection/views/collection/update.php` | collection — collection / update (تعديل سجل) (تقديري من الكود) | تعديل سجل موجود في collection. | `backend/modules/collection/views/collection/update.php` |
| `modules/collection/views/collection/view.php` | collection — collection / view (عرض تفاصيل) (تقديري من الكود) | عرض تفاصيل سجل في collection. | `backend/modules/collection/views/collection/view.php` |
| `modules/companies/views/companies/_parital/company_banks.php` | companies — _parital / company_banks (تقديري من الكود) | واجهة company_banks ضمن _parital. | `backend/modules/companies/views/companies/_parital/company_banks.php` |
| `modules/companies/views/companies/create.php` | إضافة مُستثمر جديد | إنشاء سجل جديد في companies. | `backend/modules/companies/views/companies/create.php` |
| `modules/companies/views/companies/index.php` | المُستثمرين | عرض وإدارة سجلات companies. | `backend/modules/companies/views/companies/index.php` |
| `modules/companies/views/companies/update.php` | تعديل بيانات مُستثمر | تعديل سجل موجود في companies. | `backend/modules/companies/views/companies/update.php` |
| `modules/companies/views/companies/view.php` | عرض المُستثمر: | عرض تفاصيل سجل في companies. | `backend/modules/companies/views/companies/view.php` |
| `modules/companyBanks/views/company-banks/create.php` | companyBanks — company-banks / create (إنشاء سجل جديد) (تقديري من الكود) | إنشاء سجل جديد في company banks. | `backend/modules/companyBanks/views/company-banks/create.php` |
| `modules/companyBanks/views/company-banks/index.php` | Company Banks (تقديري من الكود) | عرض وإدارة سجلات company banks. | `backend/modules/companyBanks/views/company-banks/index.php` |
| `modules/companyBanks/views/company-banks/update.php` | companyBanks — company-banks / update (تعديل سجل) (تقديري من الكود) | تعديل سجل موجود في company banks. | `backend/modules/companyBanks/views/company-banks/update.php` |
| `modules/companyBanks/views/company-banks/view.php` | companyBanks — company-banks / view (عرض تفاصيل) (تقديري من الكود) | عرض تفاصيل سجل في company banks. | `backend/modules/companyBanks/views/company-banks/view.php` |
| `modules/companyBanks/views/default/index.php` | context->action->uniqueId ?> (تقديري من الكود) | عرض وإدارة سجلات default. | `backend/modules/companyBanks/views/default/index.php` |
| `modules/connectionResponse/views/connection-response/create.php` | connectionResponse — connection-response / create (إنشاء سجل جديد) (تقديري من الكود) | إنشاء سجل جديد في connection response. | `backend/modules/connectionResponse/views/connection-response/create.php` |
| `modules/connectionResponse/views/connection-response/index.php` | Connection Responses (تقديري من الكود) | عرض وإدارة سجلات connection response. | `backend/modules/connectionResponse/views/connection-response/index.php` |
| `modules/connectionResponse/views/connection-response/update.php` | connectionResponse — connection-response / update (تعديل سجل) (تقديري من الكود) | تعديل سجل موجود في connection response. | `backend/modules/connectionResponse/views/connection-response/update.php` |
| `modules/connectionResponse/views/connection-response/view.php` | connectionResponse — connection-response / view (عرض تفاصيل) (تقديري من الكود) | عرض تفاصيل سجل في connection response. | `backend/modules/connectionResponse/views/connection-response/view.php` |
| `modules/contactType/views/contact-type/create.php` | contactType — contact-type / create (إنشاء سجل جديد) (تقديري من الكود) | إنشاء سجل جديد في contact type. | `backend/modules/contactType/views/contact-type/create.php` |
| `modules/contactType/views/contact-type/index.php` | Contact Types (تقديري من الكود) | عرض وإدارة سجلات contact type. | `backend/modules/contactType/views/contact-type/index.php` |
| `modules/contactType/views/contact-type/update.php` | contactType — contact-type / update (تعديل سجل) (تقديري من الكود) | تعديل سجل موجود في contact type. | `backend/modules/contactType/views/contact-type/update.php` |
| `modules/contactType/views/contact-type/view.php` | contactType — contact-type / view (عرض تفاصيل) (تقديري من الكود) | عرض تفاصيل سجل في contact type. | `backend/modules/contactType/views/contact-type/view.php` |
| `modules/contractDocumentFile/views/contract-document-file/create.php` | contractDocumentFile — contract-document-file / create (إنشاء سجل جديد) (تقديري من الكود) | إنشاء سجل جديد في contract document file. | `backend/modules/contractDocumentFile/views/contract-document-file/create.php` |
| `modules/contractDocumentFile/views/contract-document-file/index.php` | contractDocumentFile — contract-document-file / index (قائمة / لوحة) (تقديري من الكود) | عرض وإدارة سجلات contract document file. | `backend/modules/contractDocumentFile/views/contract-document-file/index.php` |
| `modules/contractDocumentFile/views/contract-document-file/update.php` | contractDocumentFile — contract-document-file / update (تعديل سجل) (تقديري من الكود) | تعديل سجل موجود في contract document file. | `backend/modules/contractDocumentFile/views/contract-document-file/update.php` |
| `modules/contractDocumentFile/views/contract-document-file/view.php` | contractDocumentFile — contract-document-file / view (عرض تفاصيل) (تقديري من الكود) | عرض تفاصيل سجل في contract document file. | `backend/modules/contractDocumentFile/views/contract-document-file/view.php` |
| `modules/contractInstallment/views/contract-installment/create.php` | contractInstallment — contract-installment / create (إنشاء سجل جديد) (تقديري من الكود) | إنشاء سجل جديد في contract installment. | `backend/modules/contractInstallment/views/contract-installment/create.php` |
| `modules/contractInstallment/views/contract-installment/index.php` | contractInstallment — contract-installment / index (قائمة / لوحة) (تقديري من الكود) | عرض وإدارة سجلات contract installment. | `backend/modules/contractInstallment/views/contract-installment/index.php` |
| `modules/contractInstallment/views/contract-installment/print.php` | contractInstallment — contract-installment / print (طباعة) (تقديري من الكود) | عرض نسخة قابلة للطباعة. | `backend/modules/contractInstallment/views/contract-installment/print.php` |
| `modules/contractInstallment/views/contract-installment/update.php` | contractInstallment — contract-installment / update (تعديل سجل) (تقديري من الكود) | تعديل سجل موجود في contract installment. | `backend/modules/contractInstallment/views/contract-installment/update.php` |
| `modules/contractInstallment/views/contract-installment/verify-receipt.php` | تحقق من الإيصال | واجهة verify-receipt ضمن contract installment. | `backend/modules/contractInstallment/views/contract-installment/verify-receipt.php` |
| `modules/contractInstallment/views/contract-installment/view.php` | contractInstallment — contract-installment / view (عرض تفاصيل) (تقديري من الكود) | عرض تفاصيل سجل في contract installment. | `backend/modules/contractInstallment/views/contract-installment/view.php` |
| `modules/contracts/views/contracts/create.php` | إنشاء عقد جديد | إنشاء سجل جديد في contracts. | `backend/modules/contracts/views/contracts/create.php` |
| `modules/contracts/views/contracts/first_page.php` | contracts — contracts / first_page (تقديري من الكود) | واجهة first_page ضمن contracts. | `backend/modules/contracts/views/contracts/first_page.php` |
| `modules/contracts/views/contracts/index-legal-department.php` | الدائرة القانونية | واجهة index-legal-department ضمن contracts. | `backend/modules/contracts/views/contracts/index-legal-department.php` |
| `modules/contracts/views/contracts/index.php` | العقود | عرض وإدارة سجلات contracts. | `backend/modules/contracts/views/contracts/index.php` |
| `modules/contracts/views/contracts/print.php` | عقد بيع | عرض نسخة قابلة للطباعة. | `backend/modules/contracts/views/contracts/print.php` |
| `modules/contracts/views/contracts/second_page.php` | contracts — contracts / second_page (تقديري من الكود) | واجهة second_page ضمن contracts. | `backend/modules/contracts/views/contracts/second_page.php` |
| `modules/contracts/views/contracts/update.php` | تعديل العقد # | تعديل سجل موجود في contracts. | `backend/modules/contracts/views/contracts/update.php` |
| `modules/contracts/views/contracts/view.php` | العقد # | عرض تفاصيل سجل في contracts. | `backend/modules/contracts/views/contracts/view.php` |
| `modules/court/views/court/create.php` | court — court / create (إنشاء سجل جديد) (تقديري من الكود) | إنشاء سجل جديد في court. | `backend/modules/court/views/court/create.php` |
| `modules/court/views/court/index.php` | court — court / index (قائمة / لوحة) (تقديري من الكود) | عرض وإدارة سجلات court. | `backend/modules/court/views/court/index.php` |
| `modules/court/views/court/update.php` | court — court / update (تعديل سجل) (تقديري من الكود) | تعديل سجل موجود في court. | `backend/modules/court/views/court/update.php` |
| `modules/court/views/court/view.php` | court — court / view (عرض تفاصيل) (تقديري من الكود) | عرض تفاصيل سجل في court. | `backend/modules/court/views/court/view.php` |
| `modules/cousins/views/cousins/create.php` | cousins — cousins / create (إنشاء سجل جديد) (تقديري من الكود) | إنشاء سجل جديد في cousins. | `backend/modules/cousins/views/cousins/create.php` |
| `modules/cousins/views/cousins/index.php` | Cousins (تقديري من الكود) | عرض وإدارة سجلات cousins. | `backend/modules/cousins/views/cousins/index.php` |
| `modules/cousins/views/cousins/update.php` | cousins — cousins / update (تعديل سجل) (تقديري من الكود) | تعديل سجل موجود في cousins. | `backend/modules/cousins/views/cousins/update.php` |
| `modules/cousins/views/cousins/view.php` | cousins — cousins / view (عرض تفاصيل) (تقديري من الكود) | عرض تفاصيل سجل في cousins. | `backend/modules/cousins/views/cousins/view.php` |
| `modules/customers/views/customers/contact_form.php` | customers — customers / contact_form (تقديري من الكود) | واجهة contact_form ضمن customers. | `backend/modules/customers/views/customers/contact_form.php` |
| `modules/customers/views/customers/contact_update.php` | customers — customers / contact_update (تقديري من الكود) | واجهة contact_update ضمن customers. | `backend/modules/customers/views/customers/contact_update.php` |
| `modules/customers/views/customers/create-summary.php` | تم إضافة العميل بنجاح | واجهة create-summary ضمن customers. | `backend/modules/customers/views/customers/create-summary.php` |
| `modules/customers/views/customers/create.php` | إضافة عميل جديد | إنشاء سجل جديد في customers. | `backend/modules/customers/views/customers/create.php` |
| `modules/customers/views/customers/index.php` | العملاء | عرض وإدارة سجلات customers. | `backend/modules/customers/views/customers/index.php` |
| `modules/customers/views/customers/partial/address.php` | customers — partial / address (تقديري من الكود) | واجهة address ضمن partial. | `backend/modules/customers/views/customers/partial/address.php` |
| `modules/customers/views/customers/partial/customer_documents.php` | customers — partial / customer_documents (تقديري من الكود) | واجهة customer_documents ضمن partial. | `backend/modules/customers/views/customers/partial/customer_documents.php` |
| `modules/customers/views/customers/partial/phone_numbers.php` | customers — partial / phone_numbers (تقديري من الكود) | واجهة phone_numbers ضمن partial. | `backend/modules/customers/views/customers/partial/phone_numbers.php` |
| `modules/customers/views/customers/partial/real_estate.php` | customers — partial / real_estate (تقديري من الكود) | واجهة real_estate ضمن partial. | `backend/modules/customers/views/customers/partial/real_estate.php` |
| `modules/customers/views/customers/update.php` | تعديل: | تعديل سجل موجود في customers. | `backend/modules/customers/views/customers/update.php` |
| `modules/customers/views/customers/view.php` | العميل: | عرض تفاصيل سجل في customers. | `backend/modules/customers/views/customers/view.php` |
| `modules/dektrium/user/views/admin/create.php` | dektrium — admin / create (إنشاء سجل جديد) (تقديري من الكود) | إنشاء سجل جديد في admin. | `backend/modules/dektrium/user/views/admin/create.php` |
| `modules/dektrium/user/views/admin/index.php` | dektrium — admin / index (قائمة / لوحة) (تقديري من الكود) | عرض وإدارة سجلات admin. | `backend/modules/dektrium/user/views/admin/index.php` |
| `modules/dektrium/user/views/admin/update.php` | dektrium — admin / update (تعديل سجل) (تقديري من الكود) | تعديل سجل موجود في admin. | `backend/modules/dektrium/user/views/admin/update.php` |
| `modules/dektrium/user/views/message.php` | dektrium — views / message (تقديري من الكود) | واجهة message ضمن views. | `backend/modules/dektrium/user/views/message.php` |
| `modules/dektrium/user/views/profile/show.php` | dektrium — profile / show (تقديري من الكود) | واجهة show ضمن profile. | `backend/modules/dektrium/user/views/profile/show.php` |
| `modules/dektrium/user/views/recovery/request.php` | dektrium — recovery / request (تقديري من الكود) | واجهة request ضمن recovery. | `backend/modules/dektrium/user/views/recovery/request.php` |
| `modules/dektrium/user/views/recovery/reset.php` | dektrium — recovery / reset (تقديري من الكود) | واجهة reset ضمن recovery. | `backend/modules/dektrium/user/views/recovery/reset.php` |
| `modules/dektrium/user/views/registration/connect.php` | dektrium — registration / connect (تقديري من الكود) | واجهة connect ضمن registration. | `backend/modules/dektrium/user/views/registration/connect.php` |
| `modules/dektrium/user/views/registration/register.php` | dektrium — registration / register (تقديري من الكود) | واجهة register ضمن registration. | `backend/modules/dektrium/user/views/registration/register.php` |
| `modules/dektrium/user/views/registration/resend.php` | dektrium — registration / resend (تقديري من الكود) | واجهة resend ضمن registration. | `backend/modules/dektrium/user/views/registration/resend.php` |
| `modules/dektrium/user/views/security/login.php` | dektrium — security / login (تسجيل الدخول) (تقديري من الكود) | مصادقة المستخدم والدخول للنظام. | `backend/modules/dektrium/user/views/security/login.php` |
| `modules/dektrium/user/views/settings/account.php` | dektrium — settings / account (تقديري من الكود) | واجهة account ضمن settings. | `backend/modules/dektrium/user/views/settings/account.php` |
| `modules/dektrium/user/views/settings/networks.php` | dektrium — settings / networks (تقديري من الكود) | واجهة networks ضمن settings. | `backend/modules/dektrium/user/views/settings/networks.php` |
| `modules/dektrium/user/views/settings/profile.php` | dektrium — settings / profile (تقديري من الكود) | واجهة profile ضمن settings. | `backend/modules/dektrium/user/views/settings/profile.php` |
| `modules/dektrium/user/widgets/views/login.php` | dektrium — views / login (تسجيل الدخول) (تقديري من الكود) | مصادقة المستخدم والدخول للنظام. | `backend/modules/dektrium/user/widgets/views/login.php` |
| `modules/department/views/department/create.php` | department — department / create (إنشاء سجل جديد) (تقديري من الكود) | إنشاء سجل جديد في department. | `backend/modules/department/views/department/create.php` |
| `modules/department/views/department/index.php` | department — department / index (قائمة / لوحة) (تقديري من الكود) | عرض وإدارة سجلات department. | `backend/modules/department/views/department/index.php` |
| `modules/department/views/department/update.php` | department — department / update (تعديل سجل) (تقديري من الكود) | تعديل سجل موجود في department. | `backend/modules/department/views/department/update.php` |
| `modules/department/views/department/view.php` | department — department / view (عرض تفاصيل) (تقديري من الكود) | عرض تفاصيل سجل في department. | `backend/modules/department/views/department/view.php` |
| `modules/designation/views/designation/create.php` | designation — designation / create (إنشاء سجل جديد) (تقديري من الكود) | إنشاء سجل جديد في designation. | `backend/modules/designation/views/designation/create.php` |
| `modules/designation/views/designation/index.php` | المسميات الوظيفية والأقسام | عرض وإدارة سجلات designation. | `backend/modules/designation/views/designation/index.php` |
| `modules/designation/views/designation/update.php` | designation — designation / update (تعديل سجل) (تقديري من الكود) | تعديل سجل موجود في designation. | `backend/modules/designation/views/designation/update.php` |
| `modules/designation/views/designation/view.php` | designation — designation / view (عرض تفاصيل) (تقديري من الكود) | عرض تفاصيل سجل في designation. | `backend/modules/designation/views/designation/view.php` |
| `modules/divisionsCollection/views/default/index.php` | context->action->uniqueId ?> (تقديري من الكود) | عرض وإدارة سجلات default. | `backend/modules/divisionsCollection/views/default/index.php` |
| `modules/divisionsCollection/views/divisions-collection/create.php` | divisionsCollection — divisions-collection / create (إنشاء سجل جديد) (تقديري من الكود) | إنشاء سجل جديد في divisions collection. | `backend/modules/divisionsCollection/views/divisions-collection/create.php` |
| `modules/divisionsCollection/views/divisions-collection/index.php` | Divisions Collections (تقديري من الكود) | عرض وإدارة سجلات divisions collection. | `backend/modules/divisionsCollection/views/divisions-collection/index.php` |
| `modules/divisionsCollection/views/divisions-collection/update.php` | divisionsCollection — divisions-collection / update (تعديل سجل) (تقديري من الكود) | تعديل سجل موجود في divisions collection. | `backend/modules/divisionsCollection/views/divisions-collection/update.php` |
| `modules/divisionsCollection/views/divisions-collection/view.php` | divisionsCollection — divisions-collection / view (عرض تفاصيل) (تقديري من الكود) | عرض تفاصيل سجل في divisions collection. | `backend/modules/divisionsCollection/views/divisions-collection/view.php` |
| `modules/diwan/views/diwan/correspondence_index.php` | المراسلات والتبليغات | واجهة correspondence_index ضمن diwan. | `backend/modules/diwan/views/diwan/correspondence_index.php` |
| `modules/diwan/views/diwan/correspondence_view.php` | مراسلة # | واجهة correspondence_view ضمن diwan. | `backend/modules/diwan/views/diwan/correspondence_view.php` |
| `modules/diwan/views/diwan/create.php` | قسم الديوان | إنشاء سجل جديد في diwan. | `backend/modules/diwan/views/diwan/create.php` |
| `modules/diwan/views/diwan/document_history.php` | قسم الديوان | واجهة document_history ضمن diwan. | `backend/modules/diwan/views/diwan/document_history.php` |
| `modules/diwan/views/diwan/index.php` | قسم الديوان | عرض وإدارة سجلات diwan. | `backend/modules/diwan/views/diwan/index.php` |
| `modules/diwan/views/diwan/receipt.php` | قسم الديوان | واجهة receipt ضمن diwan. | `backend/modules/diwan/views/diwan/receipt.php` |
| `modules/diwan/views/diwan/reports.php` | قسم الديوان | واجهة reports ضمن diwan. | `backend/modules/diwan/views/diwan/reports.php` |
| `modules/diwan/views/diwan/search.php` | قسم الديوان | واجهة search ضمن diwan. | `backend/modules/diwan/views/diwan/search.php` |
| `modules/diwan/views/diwan/transactions.php` | قسم الديوان | واجهة transactions ضمن diwan. | `backend/modules/diwan/views/diwan/transactions.php` |
| `modules/diwan/views/diwan/view.php` | قسم الديوان | عرض تفاصيل سجل في diwan. | `backend/modules/diwan/views/diwan/view.php` |
| `modules/documentHolder/views/document-holder/archives.php` | documentHolder — document-holder / archives (تقديري من الكود) | واجهة archives ضمن document holder. | `backend/modules/documentHolder/views/document-holder/archives.php` |
| `modules/documentHolder/views/document-holder/create.php` | documentHolder — document-holder / create (إنشاء سجل جديد) (تقديري من الكود) | إنشاء سجل جديد في document holder. | `backend/modules/documentHolder/views/document-holder/create.php` |
| `modules/documentHolder/views/document-holder/index.php` | documentHolder — document-holder / index (قائمة / لوحة) (تقديري من الكود) | عرض وإدارة سجلات document holder. | `backend/modules/documentHolder/views/document-holder/index.php` |
| `modules/documentHolder/views/document-holder/manager_index.php` | documentHolder — document-holder / manager_index (تقديري من الكود) | واجهة manager_index ضمن document holder. | `backend/modules/documentHolder/views/document-holder/manager_index.php` |
| `modules/documentHolder/views/document-holder/update.php` | documentHolder — document-holder / update (تعديل سجل) (تقديري من الكود) | تعديل سجل موجود في document holder. | `backend/modules/documentHolder/views/document-holder/update.php` |
| `modules/documentHolder/views/document-holder/view.php` | documentHolder — document-holder / view (عرض تفاصيل) (تقديري من الكود) | عرض تفاصيل سجل في document holder. | `backend/modules/documentHolder/views/document-holder/view.php` |
| `modules/documentStatus/views/document-status/create.php` | documentStatus — document-status / create (إنشاء سجل جديد) (تقديري من الكود) | إنشاء سجل جديد في document status. | `backend/modules/documentStatus/views/document-status/create.php` |
| `modules/documentStatus/views/document-status/index.php` | Document Statuses (تقديري من الكود) | عرض وإدارة سجلات document status. | `backend/modules/documentStatus/views/document-status/index.php` |
| `modules/documentStatus/views/document-status/update.php` | documentStatus — document-status / update (تعديل سجل) (تقديري من الكود) | تعديل سجل موجود في document status. | `backend/modules/documentStatus/views/document-status/update.php` |
| `modules/documentStatus/views/document-status/view.php` | documentStatus — document-status / view (عرض تفاصيل) (تقديري من الكود) | عرض تفاصيل سجل في document status. | `backend/modules/documentStatus/views/document-status/view.php` |
| `modules/documentType/views/document-type/create.php` | documentType — document-type / create (إنشاء سجل جديد) (تقديري من الكود) | إنشاء سجل جديد في document type. | `backend/modules/documentType/views/document-type/create.php` |
| `modules/documentType/views/document-type/index.php` | Document Types (تقديري من الكود) | عرض وإدارة سجلات document type. | `backend/modules/documentType/views/document-type/index.php` |
| `modules/documentType/views/document-type/update.php` | documentType — document-type / update (تعديل سجل) (تقديري من الكود) | تعديل سجل موجود في document type. | `backend/modules/documentType/views/document-type/update.php` |
| `modules/documentType/views/document-type/view.php` | documentType — document-type / view (عرض تفاصيل) (تقديري من الكود) | عرض تفاصيل سجل في document type. | `backend/modules/documentType/views/document-type/view.php` |
| `modules/employee/views/employee/create.php` | employee — employee / create (إنشاء سجل جديد) (تقديري من الكود) | إنشاء سجل جديد في employee. | `backend/modules/employee/views/employee/create.php` |
| `modules/employee/views/employee/index.php` | employee — employee / index (قائمة / لوحة) (تقديري من الكود) | عرض وإدارة سجلات employee. | `backend/modules/employee/views/employee/index.php` |
| `modules/employee/views/employee/update.php` | ملفي الشخصي - | تعديل سجل موجود في employee. | `backend/modules/employee/views/employee/update.php` |
| `modules/employee/views/employee/view.php` | employee — employee / view (عرض تفاصيل) (تقديري من الكود) | عرض تفاصيل سجل في employee. | `backend/modules/employee/views/employee/view.php` |
| `modules/expenseCategories/views/expense-categories/create.php` | expenseCategories — expense-categories / create (إنشاء سجل جديد) (تقديري من الكود) | إنشاء سجل جديد في expense categories. | `backend/modules/expenseCategories/views/expense-categories/create.php` |
| `modules/expenseCategories/views/expense-categories/import.php` | expenseCategories — expense-categories / import (تقديري من الكود) | واجهة import ضمن expense categories. | `backend/modules/expenseCategories/views/expense-categories/import.php` |
| `modules/expenseCategories/views/expense-categories/index.php` | expenseCategories — expense-categories / index (قائمة / لوحة) (تقديري من الكود) | عرض وإدارة سجلات expense categories. | `backend/modules/expenseCategories/views/expense-categories/index.php` |
| `modules/expenseCategories/views/expense-categories/update.php` | expenseCategories — expense-categories / update (تعديل سجل) (تقديري من الكود) | تعديل سجل موجود في expense categories. | `backend/modules/expenseCategories/views/expense-categories/update.php` |
| `modules/expenseCategories/views/expense-categories/view.php` | expenseCategories — expense-categories / view (عرض تفاصيل) (تقديري من الكود) | عرض تفاصيل سجل في expense categories. | `backend/modules/expenseCategories/views/expense-categories/view.php` |
| `modules/expenses/views/expenses/create.php` | expenses — expenses / create (إنشاء سجل جديد) (تقديري من الكود) | إنشاء سجل جديد في expenses. | `backend/modules/expenses/views/expenses/create.php` |
| `modules/expenses/views/expenses/index.php` | الإدارة المالية | عرض وإدارة سجلات expenses. | `backend/modules/expenses/views/expenses/index.php` |
| `modules/expenses/views/expenses/update.php` | expenses — expenses / update (تعديل سجل) (تقديري من الكود) | تعديل سجل موجود في expenses. | `backend/modules/expenses/views/expenses/update.php` |
| `modules/expenses/views/expenses/view.php` | expenses — expenses / view (عرض تفاصيل) (تقديري من الكود) | عرض تفاصيل سجل في expenses. | `backend/modules/expenses/views/expenses/view.php` |
| `modules/feelings/views/feelings/create.php` | feelings — feelings / create (إنشاء سجل جديد) (تقديري من الكود) | إنشاء سجل جديد في feelings. | `backend/modules/feelings/views/feelings/create.php` |
| `modules/feelings/views/feelings/index.php` | Feelings (تقديري من الكود) | عرض وإدارة سجلات feelings. | `backend/modules/feelings/views/feelings/index.php` |
| `modules/feelings/views/feelings/update.php` | feelings — feelings / update (تعديل سجل) (تقديري من الكود) | تعديل سجل موجود في feelings. | `backend/modules/feelings/views/feelings/update.php` |
| `modules/feelings/views/feelings/view.php` | feelings — feelings / view (عرض تفاصيل) (تقديري من الكود) | عرض تفاصيل سجل في feelings. | `backend/modules/feelings/views/feelings/view.php` |
| `modules/financialTransaction/views/financial-transaction/create.php` | إضافة حركة مالية | إنشاء سجل جديد في financial transaction. | `backend/modules/financialTransaction/views/financial-transaction/create.php` |
| `modules/financialTransaction/views/financial-transaction/import.php` | استيراد كشف حساب بنكي | واجهة import ضمن financial transaction. | `backend/modules/financialTransaction/views/financial-transaction/import.php` |
| `modules/financialTransaction/views/financial-transaction/import_grid_view.php` | financialTransaction — financial-transaction / import_grid_view (تقديري من الكود) | واجهة import_grid_view ضمن financial transaction. | `backend/modules/financialTransaction/views/financial-transaction/import_grid_view.php` |
| `modules/financialTransaction/views/financial-transaction/index.php` | الإدارة المالية | عرض وإدارة سجلات financial transaction. | `backend/modules/financialTransaction/views/financial-transaction/index.php` |
| `modules/financialTransaction/views/financial-transaction/update.php` | تعديل حركة مالية # | تعديل سجل موجود في financial transaction. | `backend/modules/financialTransaction/views/financial-transaction/update.php` |
| `modules/financialTransaction/views/financial-transaction/view.php` | حركة مالية # | عرض تفاصيل سجل في financial transaction. | `backend/modules/financialTransaction/views/financial-transaction/view.php` |
| `modules/followUp/views/follow-up/clearance.php` | followUp — follow-up / clearance (تقديري من الكود) | واجهة clearance ضمن follow up. | `backend/modules/followUp/views/follow-up/clearance.php` |
| `modules/followUp/views/follow-up/create.php` | followUp — follow-up / create (إنشاء سجل جديد) (تقديري من الكود) | إنشاء سجل جديد في follow up. | `backend/modules/followUp/views/follow-up/create.php` |
| `modules/followUp/views/follow-up/index.php` | متابعة العقد # | عرض وإدارة سجلات follow up. | `backend/modules/followUp/views/follow-up/index.php` |
| `modules/followUp/views/follow-up/modals.php` | followUp — follow-up / modals (تقديري من الكود) | واجهة modals ضمن follow up. | `backend/modules/followUp/views/follow-up/modals.php` |
| `modules/followUp/views/follow-up/panel.php` | لوحة تحكم العقد # | واجهة panel ضمن follow up. | `backend/modules/followUp/views/follow-up/panel.php` |
| `modules/followUp/views/follow-up/partial/follow-up-view.php` | followUp — partial / follow-up-view (تقديري من الكود) | واجهة follow-up-view ضمن partial. | `backend/modules/followUp/views/follow-up/partial/follow-up-view.php` |
| `modules/followUp/views/follow-up/partial/next_contract.php` | followUp — partial / next_contract (تقديري من الكود) | واجهة next_contract ضمن partial. | `backend/modules/followUp/views/follow-up/partial/next_contract.php` |
| `modules/followUp/views/follow-up/partial/phone_numbers_follow_up.php` | followUp — partial / phone_numbers_follow_up (تقديري من الكود) | واجهة phone_numbers_follow_up ضمن partial. | `backend/modules/followUp/views/follow-up/partial/phone_numbers_follow_up.php` |
| `modules/followUp/views/follow-up/partial/tabs.php` | followUp — partial / tabs (تقديري من الكود) | واجهة tabs ضمن partial. | `backend/modules/followUp/views/follow-up/partial/tabs.php` |
| `modules/followUp/views/follow-up/partial/tabs/actions.php` | followUp — tabs / actions (تقديري من الكود) | واجهة actions ضمن tabs. | `backend/modules/followUp/views/follow-up/partial/tabs/actions.php` |
| `modules/followUp/views/follow-up/partial/tabs/financial.php` | followUp — tabs / financial (تقديري من الكود) | واجهة financial ضمن tabs. | `backend/modules/followUp/views/follow-up/partial/tabs/financial.php` |
| `modules/followUp/views/follow-up/partial/tabs/judiciary_customers_actions.php` | followUp — tabs / judiciary_customers_actions (تقديري من الكود) | واجهة judiciary_customers_actions ضمن tabs. | `backend/modules/followUp/views/follow-up/partial/tabs/judiciary_customers_actions.php` |
| `modules/followUp/views/follow-up/partial/tabs/loan_scheduling.php` | followUp — tabs / loan_scheduling (تقديري من الكود) | واجهة loan_scheduling ضمن tabs. | `backend/modules/followUp/views/follow-up/partial/tabs/loan_scheduling.php` |
| `modules/followUp/views/follow-up/partial/tabs/payments.php` | followUp — tabs / payments (تقديري من الكود) | واجهة payments ضمن tabs. | `backend/modules/followUp/views/follow-up/partial/tabs/payments.php` |
| `modules/followUp/views/follow-up/partial/tabs/phone_numbers.php` | followUp — tabs / phone_numbers (تقديري من الكود) | واجهة phone_numbers ضمن tabs. | `backend/modules/followUp/views/follow-up/partial/tabs/phone_numbers.php` |
| `modules/followUp/views/follow-up/phone_number_create.php` | followUp — follow-up / phone_number_create (تقديري من الكود) | واجهة phone_number_create ضمن follow up. | `backend/modules/followUp/views/follow-up/phone_number_create.php` |
| `modules/followUp/views/follow-up/phone_number_form.php` | followUp — follow-up / phone_number_form (تقديري من الكود) | واجهة phone_number_form ضمن follow up. | `backend/modules/followUp/views/follow-up/phone_number_form.php` |
| `modules/followUp/views/follow-up/phone_number_update.php` | followUp — follow-up / phone_number_update (تقديري من الكود) | واجهة phone_number_update ضمن follow up. | `backend/modules/followUp/views/follow-up/phone_number_update.php` |
| `modules/followUp/views/follow-up/printer.php` | followUp — follow-up / printer (تقديري من الكود) | واجهة printer ضمن follow up. | `backend/modules/followUp/views/follow-up/printer.php` |
| `modules/followUp/views/follow-up/tabs.php` | followUp — follow-up / tabs (تقديري من الكود) | واجهة tabs ضمن follow up. | `backend/modules/followUp/views/follow-up/tabs.php` |
| `modules/followUp/views/follow-up/update.php` | followUp — follow-up / update (تعديل سجل) (تقديري من الكود) | تعديل سجل موجود في follow up. | `backend/modules/followUp/views/follow-up/update.php` |
| `modules/followUp/views/follow-up/verify-statement.php` | تحقق من كشف الحساب | واجهة verify-statement ضمن follow up. | `backend/modules/followUp/views/follow-up/verify-statement.php` |
| `modules/followUp/views/follow-up/view.php` | title) ?> (تقديري من الكود) | عرض تفاصيل سجل في follow up. | `backend/modules/followUp/views/follow-up/view.php` |
| `modules/followUpReport/views/follow-up-report/create.php` | followUpReport — follow-up-report / create (إنشاء سجل جديد) (تقديري من الكود) | إنشاء سجل جديد في follow up report. | `backend/modules/followUpReport/views/follow-up-report/create.php` |
| `modules/followUpReport/views/follow-up-report/first_page.php` | followUpReport — follow-up-report / first_page (تقديري من الكود) | تقرير أو لوحة إحصاءات مرتبطة بالوحدة. | `backend/modules/followUpReport/views/follow-up-report/first_page.php` |
| `modules/followUpReport/views/follow-up-report/index.php` | " style="margin-left:8px;opacity:.7"> (تقديري من الكود) | عرض وإدارة سجلات follow up report. | `backend/modules/followUpReport/views/follow-up-report/index.php` |
| `modules/followUpReport/views/follow-up-report/no-contact.php` | عقود بدون أرقام تواصل | تقرير أو لوحة إحصاءات مرتبطة بالوحدة. | `backend/modules/followUpReport/views/follow-up-report/no-contact.php` |
| `modules/followUpReport/views/follow-up-report/print.php` | عقد بيع | تقرير أو لوحة إحصاءات مرتبطة بالوحدة. | `backend/modules/followUpReport/views/follow-up-report/print.php` |
| `modules/followUpReport/views/follow-up-report/update.php` | followUpReport — follow-up-report / update (تعديل سجل) (تقديري من الكود) | تعديل سجل موجود في follow up report. | `backend/modules/followUpReport/views/follow-up-report/update.php` |
| `modules/followUpReport/views/follow-up-report/view.php` | followUpReport — follow-up-report / view (عرض تفاصيل) (تقديري من الكود) | عرض تفاصيل سجل في follow up report. | `backend/modules/followUpReport/views/follow-up-report/view.php` |
| `modules/hearAboutUs/views/hear-about-us/create.php` | hearAboutUs — hear-about-us / create (إنشاء سجل جديد) (تقديري من الكود) | إنشاء سجل جديد في hear about us. | `backend/modules/hearAboutUs/views/hear-about-us/create.php` |
| `modules/hearAboutUs/views/hear-about-us/index.php` | Hear About uses (تقديري من الكود) | عرض وإدارة سجلات hear about us. | `backend/modules/hearAboutUs/views/hear-about-us/index.php` |
| `modules/hearAboutUs/views/hear-about-us/update.php` | hearAboutUs — hear-about-us / update (تعديل سجل) (تقديري من الكود) | تعديل سجل موجود في hear about us. | `backend/modules/hearAboutUs/views/hear-about-us/update.php` |
| `modules/hearAboutUs/views/hear-about-us/view.php` | hearAboutUs — hear-about-us / view (عرض تفاصيل) (تقديري من الكود) | عرض تفاصيل سجل في hear about us. | `backend/modules/hearAboutUs/views/hear-about-us/view.php` |
| `modules/holidays/views/holidays/create.php` | holidays — holidays / create (إنشاء سجل جديد) (تقديري من الكود) | إنشاء سجل جديد في holidays. | `backend/modules/holidays/views/holidays/create.php` |
| `modules/holidays/views/holidays/index.php` | holidays — holidays / index (قائمة / لوحة) (تقديري من الكود) | عرض وإدارة سجلات holidays. | `backend/modules/holidays/views/holidays/index.php` |
| `modules/holidays/views/holidays/update.php` | holidays — holidays / update (تعديل سجل) (تقديري من الكود) | تعديل سجل موجود في holidays. | `backend/modules/holidays/views/holidays/update.php` |
| `modules/holidays/views/holidays/view.php` | holidays — holidays / view (عرض تفاصيل) (تقديري من الكود) | عرض تفاصيل سجل في holidays. | `backend/modules/holidays/views/holidays/view.php` |
| `modules/hr/views/hr-attendance/create.php` | إدخال حضور يدوي | إنشاء سجل جديد في hr attendance. | `backend/modules/hr/views/hr-attendance/create.php` |
| `modules/hr/views/hr-attendance/index.php` | لوحة الحضور والانصراف | عرض وإدارة سجلات hr attendance. | `backend/modules/hr/views/hr-attendance/index.php` |
| `modules/hr/views/hr-attendance/summary.php` | ملخص الحضور الشهري | واجهة summary ضمن hr attendance. | `backend/modules/hr/views/hr-attendance/summary.php` |
| `modules/hr/views/hr-dashboard/index.php` | الموارد البشرية | عرض وإدارة سجلات hr dashboard. | `backend/modules/hr/views/hr-dashboard/index.php` |
| `modules/hr/views/hr-employee/create.php` | إضافة بيانات موظف | إنشاء سجل جديد في hr employee. | `backend/modules/hr/views/hr-employee/create.php` |
| `modules/hr/views/hr-employee/index.php` | سجل الموظفين | عرض وإدارة سجلات hr employee. | `backend/modules/hr/views/hr-employee/index.php` |
| `modules/hr/views/hr-employee/statement.php` | كشف حساب — | واجهة statement ضمن hr employee. | `backend/modules/hr/views/hr-employee/statement.php` |
| `modules/hr/views/hr-employee/update.php` | تعديل بيانات الموظف | تعديل سجل موجود في hr employee. | `backend/modules/hr/views/hr-employee/update.php` |
| `modules/hr/views/hr-employee/view.php` | ملف الموظف — | عرض تفاصيل سجل في hr employee. | `backend/modules/hr/views/hr-employee/view.php` |
| `modules/hr/views/hr-evaluation/index.php` | تقييمات الأداء | عرض وإدارة سجلات hr evaluation. | `backend/modules/hr/views/hr-evaluation/index.php` |
| `modules/hr/views/hr-field/index.php` | لوحة المهام الميدانية | عرض وإدارة سجلات hr field. | `backend/modules/hr/views/hr-field/index.php` |
| `modules/hr/views/hr-field/map.php` | خريطة تتبع المناديب | واجهة map ضمن hr field. | `backend/modules/hr/views/hr-field/map.php` |
| `modules/hr/views/hr-field/mobile-login.php` | نظام الحضور والانصراف | واجهة mobile-login ضمن hr field. | `backend/modules/hr/views/hr-field/mobile-login.php` |
| `modules/hr/views/hr-field/mobile.php` | نظام الحضور والانصراف | واجهة mobile ضمن hr field. | `backend/modules/hr/views/hr-field/mobile.php` |
| `modules/hr/views/hr-leave/index.php` | إدارة الإجازات | عرض وإدارة سجلات hr leave. | `backend/modules/hr/views/hr-leave/index.php` |
| `modules/hr/views/hr-loan/index.php` | السلف والقروض | عرض وإدارة سجلات hr loan. | `backend/modules/hr/views/hr-loan/index.php` |
| `modules/hr/views/hr-payroll/adjustments.php` | عمولات وتعديلات — | واجهة adjustments ضمن hr payroll. | `backend/modules/hr/views/hr-payroll/adjustments.php` |
| `modules/hr/views/hr-payroll/components.php` | مكونات الراتب | واجهة components ضمن hr payroll. | `backend/modules/hr/views/hr-payroll/components.php` |
| `modules/hr/views/hr-payroll/create.php` | إنشاء مسيرة رواتب جديدة | إنشاء سجل جديد في hr payroll. | `backend/modules/hr/views/hr-payroll/create.php` |
| `modules/hr/views/hr-payroll/increment-bulk-preview.php` | معاينة العلاوة التلقائية | واجهة increment-bulk-preview ضمن hr payroll. | `backend/modules/hr/views/hr-payroll/increment-bulk-preview.php` |
| `modules/hr/views/hr-payroll/increment-bulk.php` | علاوة تلقائية (حسب الأقدمية) | واجهة increment-bulk ضمن hr payroll. | `backend/modules/hr/views/hr-payroll/increment-bulk.php` |
| `modules/hr/views/hr-payroll/increment-form.php` | إنشاء علاوة سنوية جديدة | واجهة increment-form ضمن hr payroll. | `backend/modules/hr/views/hr-payroll/increment-form.php` |
| `modules/hr/views/hr-payroll/increments.php` | العلاوات السنوية | واجهة increments ضمن hr payroll. | `backend/modules/hr/views/hr-payroll/increments.php` |
| `modules/hr/views/hr-payroll/index.php` | مسيرات الرواتب | عرض وإدارة سجلات hr payroll. | `backend/modules/hr/views/hr-payroll/index.php` |
| `modules/hr/views/hr-payroll/payslip.php` | كشف راتب — | واجهة payslip ضمن hr payroll. | `backend/modules/hr/views/hr-payroll/payslip.php` |
| `modules/hr/views/hr-payroll/view.php` | مسيرة الرواتب — | عرض تفاصيل سجل في hr payroll. | `backend/modules/hr/views/hr-payroll/view.php` |
| `modules/hr/views/hr-report/index.php` | تقارير الموارد البشرية | عرض وإدارة سجلات hr report. | `backend/modules/hr/views/hr-report/index.php` |
| `modules/hr/views/hr-shift/form.php` | title ?> (تقديري من الكود) | واجهة form ضمن hr shift. | `backend/modules/hr/views/hr-shift/form.php` |
| `modules/hr/views/hr-shift/index.php` | إدارة الورديات | عرض وإدارة سجلات hr shift. | `backend/modules/hr/views/hr-shift/index.php` |
| `modules/hr/views/hr-tracking-api/attendance-board.php` | لوحة الحضور الموحّدة | واجهة attendance-board ضمن hr tracking api. | `backend/modules/hr/views/hr-tracking-api/attendance-board.php` |
| `modules/hr/views/hr-tracking-api/live-map.php` | التتبع المباشر — خريطة حية | واجهة live-map ضمن hr tracking api. | `backend/modules/hr/views/hr-tracking-api/live-map.php` |
| `modules/hr/views/hr-tracking-api/mobile-attendance.php` | hr — hr-tracking-api / mobile-attendance (تقديري من الكود) | واجهة mobile-attendance ضمن hr tracking api. | `backend/modules/hr/views/hr-tracking-api/mobile-attendance.php` |
| `modules/hr/views/hr-tracking-api/mobile-login.php` | نظام الحضور الذكي | واجهة mobile-login ضمن hr tracking api. | `backend/modules/hr/views/hr-tracking-api/mobile-login.php` |
| `modules/hr/views/hr-tracking-report/index.php` | تحليلات الحضور والتتبع | عرض وإدارة سجلات hr tracking report. | `backend/modules/hr/views/hr-tracking-report/index.php` |
| `modules/hr/views/hr-tracking-report/monthly.php` | التقرير الشهري — | تقرير أو لوحة إحصاءات مرتبطة بالوحدة. | `backend/modules/hr/views/hr-tracking-report/monthly.php` |
| `modules/hr/views/hr-tracking-report/punctuality.php` | تقرير الانضباط الوظيفي | تقرير أو لوحة إحصاءات مرتبطة بالوحدة. | `backend/modules/hr/views/hr-tracking-report/punctuality.php` |
| `modules/hr/views/hr-tracking-report/violations.php` | تقرير المخالفات والأمان | تقرير أو لوحة إحصاءات مرتبطة بالوحدة. | `backend/modules/hr/views/hr-tracking-report/violations.php` |
| `modules/hr/views/hr-work-zone/form.php` | title ?> (تقديري من الكود) | واجهة form ضمن hr work zone. | `backend/modules/hr/views/hr-work-zone/form.php` |
| `modules/hr/views/hr-work-zone/index.php` | مناطق العمل (Geofences) | عرض وإدارة سجلات hr work zone. | `backend/modules/hr/views/hr-work-zone/index.php` |
| `modules/income/views/income/create.php` | income — income / create (إنشاء سجل جديد) (تقديري من الكود) | إنشاء سجل جديد في income. | `backend/modules/income/views/income/create.php` |
| `modules/income/views/income/income-item-list.php` | الإدارة المالية | واجهة income-item-list ضمن income. | `backend/modules/income/views/income/income-item-list.php` |
| `modules/income/views/income/income_list_form.php` | income — income / income_list_form (تقديري من الكود) | واجهة income_list_form ضمن income. | `backend/modules/income/views/income/income_list_form.php` |
| `modules/income/views/income/index.php` | مجموع الاقساط الكلي:userTotalInstallment; ?> | عرض وإدارة سجلات income. | `backend/modules/income/views/income/index.php` |
| `modules/income/views/income/update.php` | income — income / update (تعديل سجل) (تقديري من الكود) | تعديل سجل موجود في income. | `backend/modules/income/views/income/update.php` |
| `modules/income/views/income/view.php` | income — income / view (عرض تفاصيل) (تقديري من الكود) | عرض تفاصيل سجل في income. | `backend/modules/income/views/income/view.php` |
| `modules/incomeCategory/views/income-category/create.php` | incomeCategory — income-category / create (إنشاء سجل جديد) (تقديري من الكود) | إنشاء سجل جديد في income category. | `backend/modules/incomeCategory/views/income-category/create.php` |
| `modules/incomeCategory/views/income-category/index.php` | incomeCategory — income-category / index (قائمة / لوحة) (تقديري من الكود) | عرض وإدارة سجلات income category. | `backend/modules/incomeCategory/views/income-category/index.php` |
| `modules/incomeCategory/views/income-category/update.php` | incomeCategory — income-category / update (تعديل سجل) (تقديري من الكود) | تعديل سجل موجود في income category. | `backend/modules/incomeCategory/views/income-category/update.php` |
| `modules/incomeCategory/views/income-category/view.php` | incomeCategory — income-category / view (عرض تفاصيل) (تقديري من الكود) | عرض تفاصيل سجل في income category. | `backend/modules/incomeCategory/views/income-category/view.php` |
| `modules/inventoryInvoices/views/default/index.php` | context->action->uniqueId ?> (تقديري من الكود) | عرض وإدارة سجلات default. | `backend/modules/inventoryInvoices/views/default/index.php` |
| `modules/inventoryInvoices/views/inventory-invoices/create-wizard.php` | فاتورة توريد جديدة (معالج) | واجهة create-wizard ضمن inventory invoices. | `backend/modules/inventoryInvoices/views/inventory-invoices/create-wizard.php` |
| `modules/inventoryInvoices/views/inventory-invoices/create.php` | inventoryInvoices — inventory-invoices / create (إنشاء سجل جديد) (تقديري من الكود) | إنشاء سجل جديد في inventory invoices. | `backend/modules/inventoryInvoices/views/inventory-invoices/create.php` |
| `modules/inventoryInvoices/views/inventory-invoices/index.php` | إدارة المخزون | عرض وإدارة سجلات inventory invoices. | `backend/modules/inventoryInvoices/views/inventory-invoices/index.php` |
| `modules/inventoryInvoices/views/inventory-invoices/reject-reception.php` | رفض استلام الفاتورة # | واجهة reject-reception ضمن inventory invoices. | `backend/modules/inventoryInvoices/views/inventory-invoices/reject-reception.php` |
| `modules/inventoryInvoices/views/inventory-invoices/update.php` | inventoryInvoices — inventory-invoices / update (تعديل سجل) (تقديري من الكود) | تعديل سجل موجود في inventory invoices. | `backend/modules/inventoryInvoices/views/inventory-invoices/update.php` |
| `modules/inventoryInvoices/views/inventory-invoices/view.php` | فاتورة # | عرض تفاصيل سجل في inventory invoices. | `backend/modules/inventoryInvoices/views/inventory-invoices/view.php` |
| `modules/inventoryItemQuantities/views/inventory-item-quantities/create.php` | inventoryItemQuantities — inventory-item-quantities / create (إنشاء سجل جديد) (تقديري من الكود) | إنشاء سجل جديد في inventory item quantities. | `backend/modules/inventoryItemQuantities/views/inventory-item-quantities/create.php` |
| `modules/inventoryItemQuantities/views/inventory-item-quantities/index.php` | إدارة المخزون | عرض وإدارة سجلات inventory item quantities. | `backend/modules/inventoryItemQuantities/views/inventory-item-quantities/index.php` |
| `modules/inventoryItemQuantities/views/inventory-item-quantities/update.php` | inventoryItemQuantities — inventory-item-quantities / update (تعديل سجل) (تقديري من الكود) | تعديل سجل موجود في inventory item quantities. | `backend/modules/inventoryItemQuantities/views/inventory-item-quantities/update.php` |
| `modules/inventoryItemQuantities/views/inventory-item-quantities/view.php` | inventoryItemQuantities — inventory-item-quantities / view (عرض تفاصيل) (تقديري من الكود) | عرض تفاصيل سجل في inventory item quantities. | `backend/modules/inventoryItemQuantities/views/inventory-item-quantities/view.php` |
| `modules/inventoryItems/views/inventory-items/create.php` | inventoryItems — inventory-items / create (إنشاء سجل جديد) (تقديري من الكود) | إنشاء سجل جديد في inventory items. | `backend/modules/inventoryItems/views/inventory-items/create.php` |
| `modules/inventoryItems/views/inventory-items/dashboard.php` | إدارة المخزون | واجهة dashboard ضمن inventory items. | `backend/modules/inventoryItems/views/inventory-items/dashboard.php` |
| `modules/inventoryItems/views/inventory-items/index.php` | إدارة المخزون | عرض وإدارة سجلات inventory items. | `backend/modules/inventoryItems/views/inventory-items/index.php` |
| `modules/inventoryItems/views/inventory-items/index_item_query.php` | إدارة المخزون | واجهة index_item_query ضمن inventory items. | `backend/modules/inventoryItems/views/inventory-items/index_item_query.php` |
| `modules/inventoryItems/views/inventory-items/items.php` | إدارة المخزون | واجهة items ضمن inventory items. | `backend/modules/inventoryItems/views/inventory-items/items.php` |
| `modules/inventoryItems/views/inventory-items/movements.php` | إدارة المخزون | واجهة movements ضمن inventory items. | `backend/modules/inventoryItems/views/inventory-items/movements.php` |
| `modules/inventoryItems/views/inventory-items/serial-numbers.php` | الأرقام التسلسلية — إدارة المخزون | واجهة serial-numbers ضمن inventory items. | `backend/modules/inventoryItems/views/inventory-items/serial-numbers.php` |
| `modules/inventoryItems/views/inventory-items/settings.php` | إدارة المخزون | واجهة settings ضمن inventory items. | `backend/modules/inventoryItems/views/inventory-items/settings.php` |
| `modules/inventoryItems/views/inventory-items/update.php` | inventoryItems — inventory-items / update (تعديل سجل) (تقديري من الكود) | تعديل سجل موجود في inventory items. | `backend/modules/inventoryItems/views/inventory-items/update.php` |
| `modules/inventoryItems/views/inventory-items/view.php` | inventoryItems — inventory-items / view (عرض تفاصيل) (تقديري من الكود) | عرض تفاصيل سجل في inventory items. | `backend/modules/inventoryItems/views/inventory-items/view.php` |
| `modules/inventoryStockLocations/views/inventory-stock-locations/create.php` | inventoryStockLocations — inventory-stock-locations / create (إنشاء سجل جديد) (تقديري من الكود) | إنشاء سجل جديد في inventory stock locations. | `backend/modules/inventoryStockLocations/views/inventory-stock-locations/create.php` |
| `modules/inventoryStockLocations/views/inventory-stock-locations/index.php` | إدارة المخزون | عرض وإدارة سجلات inventory stock locations. | `backend/modules/inventoryStockLocations/views/inventory-stock-locations/index.php` |
| `modules/inventoryStockLocations/views/inventory-stock-locations/update.php` | inventoryStockLocations — inventory-stock-locations / update (تعديل سجل) (تقديري من الكود) | تعديل سجل موجود في inventory stock locations. | `backend/modules/inventoryStockLocations/views/inventory-stock-locations/update.php` |
| `modules/inventoryStockLocations/views/inventory-stock-locations/view.php` | inventoryStockLocations — inventory-stock-locations / view (عرض تفاصيل) (تقديري من الكود) | عرض تفاصيل سجل في inventory stock locations. | `backend/modules/inventoryStockLocations/views/inventory-stock-locations/view.php` |
| `modules/inventorySuppliers/views/inventory-suppliers/create.php` | inventorySuppliers — inventory-suppliers / create (إنشاء سجل جديد) (تقديري من الكود) | إنشاء سجل جديد في inventory suppliers. | `backend/modules/inventorySuppliers/views/inventory-suppliers/create.php` |
| `modules/inventorySuppliers/views/inventory-suppliers/index.php` | إدارة المخزون | عرض وإدارة سجلات inventory suppliers. | `backend/modules/inventorySuppliers/views/inventory-suppliers/index.php` |
| `modules/inventorySuppliers/views/inventory-suppliers/update.php` | inventorySuppliers — inventory-suppliers / update (تعديل سجل) (تقديري من الكود) | تعديل سجل موجود في inventory suppliers. | `backend/modules/inventorySuppliers/views/inventory-suppliers/update.php` |
| `modules/inventorySuppliers/views/inventory-suppliers/view.php` | inventorySuppliers — inventory-suppliers / view (عرض تفاصيل) (تقديري من الكود) | عرض تفاصيل سجل في inventory suppliers. | `backend/modules/inventorySuppliers/views/inventory-suppliers/view.php` |
| `modules/invoice/views/invoice/create.php` | invoice — invoice / create (إنشاء سجل جديد) (تقديري من الكود) | إنشاء سجل جديد في invoice. | `backend/modules/invoice/views/invoice/create.php` |
| `modules/invoice/views/invoice/index.php` | invoice — invoice / index (قائمة / لوحة) (تقديري من الكود) | عرض وإدارة سجلات invoice. | `backend/modules/invoice/views/invoice/index.php` |
| `modules/invoice/views/invoice/update.php` | invoice — invoice / update (تعديل سجل) (تقديري من الكود) | تعديل سجل موجود في invoice. | `backend/modules/invoice/views/invoice/update.php` |
| `modules/invoice/views/invoice/view.php` | invoice — invoice / view (عرض تفاصيل) (تقديري من الكود) | عرض تفاصيل سجل في invoice. | `backend/modules/invoice/views/invoice/view.php` |
| `modules/items/views/items/create.php` | items — items / create (إنشاء سجل جديد) (تقديري من الكود) | إنشاء سجل جديد في items. | `backend/modules/items/views/items/create.php` |
| `modules/items/views/items/index.php` | items — items / index (قائمة / لوحة) (تقديري من الكود) | عرض وإدارة سجلات items. | `backend/modules/items/views/items/index.php` |
| `modules/items/views/items/update.php` | items — items / update (تعديل سجل) (تقديري من الكود) | تعديل سجل موجود في items. | `backend/modules/items/views/items/update.php` |
| `modules/items/views/items/view.php` | items — items / view (عرض تفاصيل) (تقديري من الكود) | عرض تفاصيل سجل في items. | `backend/modules/items/views/items/view.php` |
| `modules/itemsInventoryInvoices/views/default/index.php` | context->action->uniqueId ?> (تقديري من الكود) | عرض وإدارة سجلات default. | `backend/modules/itemsInventoryInvoices/views/default/index.php` |
| `modules/itemsInventoryInvoices/views/items-inventory-invoices/create.php` | itemsInventoryInvoices — items-inventory-invoices / create (إنشاء سجل جديد) (تقديري من الكود) | إنشاء سجل جديد في items inventory invoices. | `backend/modules/itemsInventoryInvoices/views/items-inventory-invoices/create.php` |
| `modules/itemsInventoryInvoices/views/items-inventory-invoices/index.php` | Items Inventory Invoices (تقديري من الكود) | عرض وإدارة سجلات items inventory invoices. | `backend/modules/itemsInventoryInvoices/views/items-inventory-invoices/index.php` |
| `modules/itemsInventoryInvoices/views/items-inventory-invoices/update.php` | itemsInventoryInvoices — items-inventory-invoices / update (تعديل سجل) (تقديري من الكود) | تعديل سجل موجود في items inventory invoices. | `backend/modules/itemsInventoryInvoices/views/items-inventory-invoices/update.php` |
| `modules/itemsInventoryInvoices/views/items-inventory-invoices/view.php` | itemsInventoryInvoices — items-inventory-invoices / view (عرض تفاصيل) (تقديري من الكود) | عرض تفاصيل سجل في items inventory invoices. | `backend/modules/itemsInventoryInvoices/views/items-inventory-invoices/view.php` |
| `modules/jobs/views/jobs/create.php` | إضافة جهة عمل جديدة | إنشاء سجل جديد في jobs. | `backend/modules/jobs/views/jobs/create.php` |
| `modules/jobs/views/jobs/index.php` | جهات العمل | عرض وإدارة سجلات jobs. | `backend/modules/jobs/views/jobs/index.php` |
| `modules/jobs/views/jobs/update.php` | تعديل جهة العمل: | تعديل سجل موجود في jobs. | `backend/modules/jobs/views/jobs/update.php` |
| `modules/jobs/views/jobs/view.php` | jobs — jobs / view (عرض تفاصيل) (تقديري من الكود) | عرض تفاصيل سجل في jobs. | `backend/modules/jobs/views/jobs/view.php` |
| `modules/judiciary/views/judiciary/batch_actions.php` | الإدخال المجمّع | واجهة batch_actions ضمن judiciary. | `backend/modules/judiciary/views/judiciary/batch_actions.php` |
| `modules/judiciary/views/judiciary/batch_create.php` | تجهيز القضايا — معالج جماعي | واجهة batch_create ضمن judiciary. | `backend/modules/judiciary/views/judiciary/batch_create.php` |
| `modules/judiciary/views/judiciary/batch_print.php` | judiciary — judiciary / batch_print (تقديري من الكود) | واجهة batch_print ضمن judiciary. | `backend/modules/judiciary/views/judiciary/batch_print.php` |
| `modules/judiciary/views/judiciary/cases_report.php` | كشف المثابره | واجهة cases_report ضمن judiciary. | `backend/modules/judiciary/views/judiciary/cases_report.php` |
| `modules/judiciary/views/judiciary/cases_report_print.php` | كشف المثابره | واجهة cases_report_print ضمن judiciary. | `backend/modules/judiciary/views/judiciary/cases_report_print.php` |
| `modules/judiciary/views/judiciary/create.php` | إنشاء قضية - عقد # | إنشاء سجل جديد في judiciary. | `backend/modules/judiciary/views/judiciary/create.php` |
| `modules/judiciary/views/judiciary/deadline_dashboard.php` | لوحة المواعيد النهائية | واجهة deadline_dashboard ضمن judiciary. | `backend/modules/judiciary/views/judiciary/deadline_dashboard.php` |
| `modules/judiciary/views/judiciary/generate_request.php` | توليد طلب إجرائي — القضية # | واجهة generate_request ضمن judiciary. | `backend/modules/judiciary/views/judiciary/generate_request.php` |
| `modules/judiciary/views/judiciary/index.php` | القسم القانوني | عرض وإدارة سجلات judiciary. | `backend/modules/judiciary/views/judiciary/index.php` |
| `modules/judiciary/views/judiciary/print_case.php` | judiciary — judiciary / print_case (تقديري من الكود) | واجهة print_case ضمن judiciary. | `backend/modules/judiciary/views/judiciary/print_case.php` |
| `modules/judiciary/views/judiciary/report.php` | judiciary — judiciary / report (تقديري من الكود) | واجهة report ضمن judiciary. | `backend/modules/judiciary/views/judiciary/report.php` |
| `modules/judiciary/views/judiciary/update.php` | تعديل القضية # | تعديل سجل موجود في judiciary. | `backend/modules/judiciary/views/judiciary/update.php` |
| `modules/judiciary/views/judiciary/view.php` | ملف القضية # | عرض تفاصيل سجل في judiciary. | `backend/modules/judiciary/views/judiciary/view.php` |
| `modules/judiciaryActions/views/judiciary-actions/create.php` | judiciaryActions — judiciary-actions / create (إنشاء سجل جديد) (تقديري من الكود) | إنشاء سجل جديد في judiciary actions. | `backend/modules/judiciaryActions/views/judiciary-actions/create.php` |
| `modules/judiciaryActions/views/judiciary-actions/index.php` | إدارة الإجراءات القضائية | عرض وإدارة سجلات judiciary actions. | `backend/modules/judiciaryActions/views/judiciary-actions/index.php` |
| `modules/judiciaryActions/views/judiciary-actions/update.php` | judiciaryActions — judiciary-actions / update (تعديل سجل) (تقديري من الكود) | تعديل سجل موجود في judiciary actions. | `backend/modules/judiciaryActions/views/judiciary-actions/update.php` |
| `modules/judiciaryActions/views/judiciary-actions/view.php` | judiciaryActions — judiciary-actions / view (عرض تفاصيل) (تقديري من الكود) | عرض تفاصيل سجل في judiciary actions. | `backend/modules/judiciaryActions/views/judiciary-actions/view.php` |
| `modules/judiciaryAuthorities/views/judiciary-authorities/create.php` | إضافة جهة رسمية | إنشاء سجل جديد في judiciary authorities. | `backend/modules/judiciaryAuthorities/views/judiciary-authorities/create.php` |
| `modules/judiciaryAuthorities/views/judiciary-authorities/index.php` | الجهات الرسمية | عرض وإدارة سجلات judiciary authorities. | `backend/modules/judiciaryAuthorities/views/judiciary-authorities/index.php` |
| `modules/judiciaryAuthorities/views/judiciary-authorities/update.php` | تعديل: | تعديل سجل موجود في judiciary authorities. | `backend/modules/judiciaryAuthorities/views/judiciary-authorities/update.php` |
| `modules/judiciaryAuthorities/views/judiciary-authorities/view.php` | judiciaryAuthorities — judiciary-authorities / view (عرض تفاصيل) (تقديري من الكود) | عرض تفاصيل سجل في judiciary authorities. | `backend/modules/judiciaryAuthorities/views/judiciary-authorities/view.php` |
| `modules/judiciaryCustomersActions/views/judiciary-customers-actions/create-in-contract.php` | judiciaryCustomersActions — judiciary-customers-actions / create-in-contract (تقديري من الكود) | واجهة create-in-contract ضمن judiciary customers actions. | `backend/modules/judiciaryCustomersActions/views/judiciary-customers-actions/create-in-contract.php` |
| `modules/judiciaryCustomersActions/views/judiciary-customers-actions/create.php` | judiciaryCustomersActions — judiciary-customers-actions / create (إنشاء سجل جديد) (تقديري من الكود) | إنشاء سجل جديد في judiciary customers actions. | `backend/modules/judiciaryCustomersActions/views/judiciary-customers-actions/create.php` |
| `modules/judiciaryCustomersActions/views/judiciary-customers-actions/index.php` | إجراءات العملاء القضائية | عرض وإدارة سجلات judiciary customers actions. | `backend/modules/judiciaryCustomersActions/views/judiciary-customers-actions/index.php` |
| `modules/judiciaryCustomersActions/views/judiciary-customers-actions/update.php` | judiciaryCustomersActions — judiciary-customers-actions / update (تعديل سجل) (تقديري من الكود) | تعديل سجل موجود في judiciary customers actions. | `backend/modules/judiciaryCustomersActions/views/judiciary-customers-actions/update.php` |
| `modules/judiciaryCustomersActions/views/judiciary-customers-actions/view.php` | judiciaryCustomersActions — judiciary-customers-actions / view (عرض تفاصيل) (تقديري من الكود) | عرض تفاصيل سجل في judiciary customers actions. | `backend/modules/judiciaryCustomersActions/views/judiciary-customers-actions/view.php` |
| `modules/JudiciaryInformAddress/views/judiciary-inform-address/create.php` | title) ?> (تقديري من الكود) | إنشاء سجل جديد في judiciary inform address. | `backend/modules/JudiciaryInformAddress/views/judiciary-inform-address/create.php` |
| `modules/JudiciaryInformAddress/views/judiciary-inform-address/index.php` | title) ?> (تقديري من الكود) | عرض وإدارة سجلات judiciary inform address. | `backend/modules/JudiciaryInformAddress/views/judiciary-inform-address/index.php` |
| `modules/JudiciaryInformAddress/views/judiciary-inform-address/update.php` | title) ?> (تقديري من الكود) | تعديل سجل موجود في judiciary inform address. | `backend/modules/JudiciaryInformAddress/views/judiciary-inform-address/update.php` |
| `modules/JudiciaryInformAddress/views/judiciary-inform-address/view.php` | title) ?> (تقديري من الكود) | عرض تفاصيل سجل في judiciary inform address. | `backend/modules/JudiciaryInformAddress/views/judiciary-inform-address/view.php` |
| `modules/judiciaryRequestTemplates/views/judiciary-request-templates/create.php` | إضافة قالب طلب | إنشاء سجل جديد في judiciary request templates. | `backend/modules/judiciaryRequestTemplates/views/judiciary-request-templates/create.php` |
| `modules/judiciaryRequestTemplates/views/judiciary-request-templates/index.php` | قوالب الطلبات | عرض وإدارة سجلات judiciary request templates. | `backend/modules/judiciaryRequestTemplates/views/judiciary-request-templates/index.php` |
| `modules/judiciaryRequestTemplates/views/judiciary-request-templates/update.php` | تعديل: | تعديل سجل موجود في judiciary request templates. | `backend/modules/judiciaryRequestTemplates/views/judiciary-request-templates/update.php` |
| `modules/judiciaryRequestTemplates/views/judiciary-request-templates/view.php` | judiciaryRequestTemplates — judiciary-request-templates / view (عرض تفاصيل) (تقديري من الكود) | عرض تفاصيل سجل في judiciary request templates. | `backend/modules/judiciaryRequestTemplates/views/judiciary-request-templates/view.php` |
| `modules/judiciaryType/views/judiciary-type/create.php` | judiciaryType — judiciary-type / create (إنشاء سجل جديد) (تقديري من الكود) | إنشاء سجل جديد في judiciary type. | `backend/modules/judiciaryType/views/judiciary-type/create.php` |
| `modules/judiciaryType/views/judiciary-type/index.php` | judiciaryType — judiciary-type / index (قائمة / لوحة) (تقديري من الكود) | عرض وإدارة سجلات judiciary type. | `backend/modules/judiciaryType/views/judiciary-type/index.php` |
| `modules/judiciaryType/views/judiciary-type/update.php` | judiciaryType — judiciary-type / update (تعديل سجل) (تقديري من الكود) | تعديل سجل موجود في judiciary type. | `backend/modules/judiciaryType/views/judiciary-type/update.php` |
| `modules/judiciaryType/views/judiciary-type/view.php` | judiciaryType — judiciary-type / view (عرض تفاصيل) (تقديري من الكود) | عرض تفاصيل سجل في judiciary type. | `backend/modules/judiciaryType/views/judiciary-type/view.php` |
| `modules/lawyers/views/lawyers/create.php` | إضافة مفوض / وكيل | إنشاء سجل جديد في lawyers. | `backend/modules/lawyers/views/lawyers/create.php` |
| `modules/lawyers/views/lawyers/index.php` | المفوضين والوكلاء | عرض وإدارة سجلات lawyers. | `backend/modules/lawyers/views/lawyers/index.php` |
| `modules/lawyers/views/lawyers/update.php` | تعديل: | تعديل سجل موجود في lawyers. | `backend/modules/lawyers/views/lawyers/update.php` |
| `modules/lawyers/views/lawyers/view.php` | lawyers — lawyers / view (عرض تفاصيل) (تقديري من الكود) | عرض تفاصيل سجل في lawyers. | `backend/modules/lawyers/views/lawyers/view.php` |
| `modules/LawyersImage/views/default/index.php` | context->action->uniqueId ?> (تقديري من الكود) | عرض وإدارة سجلات default. | `backend/modules/LawyersImage/views/default/index.php` |
| `modules/leavePolicy/views/leave-policy/create.php` | leavePolicy — leave-policy / create (إنشاء سجل جديد) (تقديري من الكود) | إنشاء سجل جديد في leave policy. | `backend/modules/leavePolicy/views/leave-policy/create.php` |
| `modules/leavePolicy/views/leave-policy/index.php` | leavePolicy — leave-policy / index (قائمة / لوحة) (تقديري من الكود) | عرض وإدارة سجلات leave policy. | `backend/modules/leavePolicy/views/leave-policy/index.php` |
| `modules/leavePolicy/views/leave-policy/update.php` | leavePolicy — leave-policy / update (تعديل سجل) (تقديري من الكود) | تعديل سجل موجود في leave policy. | `backend/modules/leavePolicy/views/leave-policy/update.php` |
| `modules/leavePolicy/views/leave-policy/view.php` | leavePolicy — leave-policy / view (عرض تفاصيل) (تقديري من الكود) | عرض تفاصيل سجل في leave policy. | `backend/modules/leavePolicy/views/leave-policy/view.php` |
| `modules/leaveRequest/views/leave-request/create.php` | leaveRequest — leave-request / create (إنشاء سجل جديد) (تقديري من الكود) | إنشاء سجل جديد في leave request. | `backend/modules/leaveRequest/views/leave-request/create.php` |
| `modules/leaveRequest/views/leave-request/index.php` | leaveRequest — leave-request / index (قائمة / لوحة) (تقديري من الكود) | عرض وإدارة سجلات leave request. | `backend/modules/leaveRequest/views/leave-request/index.php` |
| `modules/leaveRequest/views/leave-request/suspended_vacations.php` | leaveRequest — leave-request / suspended_vacations (تقديري من الكود) | واجهة suspended_vacations ضمن leave request. | `backend/modules/leaveRequest/views/leave-request/suspended_vacations.php` |
| `modules/leaveRequest/views/leave-request/update.php` | leaveRequest — leave-request / update (تعديل سجل) (تقديري من الكود) | تعديل سجل موجود في leave request. | `backend/modules/leaveRequest/views/leave-request/update.php` |
| `modules/leaveRequest/views/leave-request/view.php` | leaveRequest — leave-request / view (عرض تفاصيل) (تقديري من الكود) | عرض تفاصيل سجل في leave request. | `backend/modules/leaveRequest/views/leave-request/view.php` |
| `modules/leaveTypes/views/leave-types/create.php` | leaveTypes — leave-types / create (إنشاء سجل جديد) (تقديري من الكود) | إنشاء سجل جديد في leave types. | `backend/modules/leaveTypes/views/leave-types/create.php` |
| `modules/leaveTypes/views/leave-types/index.php` | leaveTypes — leave-types / index (قائمة / لوحة) (تقديري من الكود) | عرض وإدارة سجلات leave types. | `backend/modules/leaveTypes/views/leave-types/index.php` |
| `modules/leaveTypes/views/leave-types/update.php` | leaveTypes — leave-types / update (تعديل سجل) (تقديري من الكود) | تعديل سجل موجود في leave types. | `backend/modules/leaveTypes/views/leave-types/update.php` |
| `modules/leaveTypes/views/leave-types/view.php` | leaveTypes — leave-types / view (عرض تفاصيل) (تقديري من الكود) | عرض تفاصيل سجل في leave types. | `backend/modules/leaveTypes/views/leave-types/view.php` |
| `modules/loanScheduling/views/loan-scheduling/create.php` | إنشاء تسوية | إنشاء سجل جديد في loan scheduling. | `backend/modules/loanScheduling/views/loan-scheduling/create.php` |
| `modules/loanScheduling/views/loan-scheduling/index.php` | الإدارة المالية | عرض وإدارة سجلات loan scheduling. | `backend/modules/loanScheduling/views/loan-scheduling/index.php` |
| `modules/loanScheduling/views/loan-scheduling/update.php` | loanScheduling — loan-scheduling / update (تعديل سجل) (تقديري من الكود) | تعديل سجل موجود في loan scheduling. | `backend/modules/loanScheduling/views/loan-scheduling/update.php` |
| `modules/loanScheduling/views/loan-scheduling/view.php` | loanScheduling — loan-scheduling / view (عرض تفاصيل) (تقديري من الكود) | عرض تفاصيل سجل في loan scheduling. | `backend/modules/loanScheduling/views/loan-scheduling/view.php` |
| `modules/location/views/location/create.php` | location — location / create (إنشاء سجل جديد) (تقديري من الكود) | إنشاء سجل جديد في location. | `backend/modules/location/views/location/create.php` |
| `modules/location/views/location/index.php` | location — location / index (قائمة / لوحة) (تقديري من الكود) | عرض وإدارة سجلات location. | `backend/modules/location/views/location/index.php` |
| `modules/location/views/location/update.php` | location — location / update (تعديل سجل) (تقديري من الكود) | تعديل سجل موجود في location. | `backend/modules/location/views/location/update.php` |
| `modules/location/views/location/view.php` | location — location / view (عرض تفاصيل) (تقديري من الكود) | عرض تفاصيل سجل في location. | `backend/modules/location/views/location/view.php` |
| `modules/movment/views/movment/create.php` | movment — movment / create (إنشاء سجل جديد) (تقديري من الكود) | إنشاء سجل جديد في movment. | `backend/modules/movment/views/movment/create.php` |
| `modules/movment/views/movment/index.php` | movment — movment / index (قائمة / لوحة) (تقديري من الكود) | عرض وإدارة سجلات movment. | `backend/modules/movment/views/movment/index.php` |
| `modules/movment/views/movment/update.php` | movment — movment / update (تعديل سجل) (تقديري من الكود) | تعديل سجل موجود في movment. | `backend/modules/movment/views/movment/update.php` |
| `modules/movment/views/movment/view.php` | movment — movment / view (عرض تفاصيل) (تقديري من الكود) | عرض تفاصيل سجل في movment. | `backend/modules/movment/views/movment/view.php` |
| `modules/notification/views/notification/create.php` | notification — notification / create (إنشاء سجل جديد) (تقديري من الكود) | إنشاء سجل جديد في notification. | `backend/modules/notification/views/notification/create.php` |
| `modules/notification/views/notification/index.php` | notification — notification / index (قائمة / لوحة) (تقديري من الكود) | عرض وإدارة سجلات notification. | `backend/modules/notification/views/notification/index.php` |
| `modules/notification/views/notification/update.php` | notification — notification / update (تعديل سجل) (تقديري من الكود) | تعديل سجل موجود في notification. | `backend/modules/notification/views/notification/update.php` |
| `modules/notification/views/notification/view.php` | notification — notification / view (عرض تفاصيل) (تقديري من الكود) | عرض تفاصيل سجل في notification. | `backend/modules/notification/views/notification/view.php` |
| `modules/officialHolidays/views/official-holidays/create.php` | إضافة عطلة رسمية | إنشاء سجل جديد في official holidays. | `backend/modules/officialHolidays/views/official-holidays/create.php` |
| `modules/officialHolidays/views/official-holidays/index.php` | العطل الرسمية | عرض وإدارة سجلات official holidays. | `backend/modules/officialHolidays/views/official-holidays/index.php` |
| `modules/officialHolidays/views/official-holidays/update.php` | تعديل: | تعديل سجل موجود في official holidays. | `backend/modules/officialHolidays/views/official-holidays/update.php` |
| `modules/officialHolidays/views/official-holidays/view.php` | officialHolidays — official-holidays / view (عرض تفاصيل) (تقديري من الكود) | عرض تفاصيل سجل في official holidays. | `backend/modules/officialHolidays/views/official-holidays/view.php` |
| `modules/paymentType/views/payment-type/create.php` | paymentType — payment-type / create (إنشاء سجل جديد) (تقديري من الكود) | إنشاء سجل جديد في payment type. | `backend/modules/paymentType/views/payment-type/create.php` |
| `modules/paymentType/views/payment-type/index.php` | Payment Types (تقديري من الكود) | عرض وإدارة سجلات payment type. | `backend/modules/paymentType/views/payment-type/index.php` |
| `modules/paymentType/views/payment-type/update.php` | paymentType — payment-type / update (تعديل سجل) (تقديري من الكود) | تعديل سجل موجود في payment type. | `backend/modules/paymentType/views/payment-type/update.php` |
| `modules/paymentType/views/payment-type/view.php` | paymentType — payment-type / view (عرض تفاصيل) (تقديري من الكود) | عرض تفاصيل سجل في payment type. | `backend/modules/paymentType/views/payment-type/view.php` |
| `modules/phoneNumbers/views/phone-numbers/create.php` | phoneNumbers — phone-numbers / create (إنشاء سجل جديد) (تقديري من الكود) | إنشاء سجل جديد في phone numbers. | `backend/modules/phoneNumbers/views/phone-numbers/create.php` |
| `modules/phoneNumbers/views/phone-numbers/index.php` | phoneNumbers — phone-numbers / index (قائمة / لوحة) (تقديري من الكود) | عرض وإدارة سجلات phone numbers. | `backend/modules/phoneNumbers/views/phone-numbers/index.php` |
| `modules/phoneNumbers/views/phone-numbers/update.php` | phoneNumbers — phone-numbers / update (تعديل سجل) (تقديري من الكود) | تعديل سجل موجود في phone numbers. | `backend/modules/phoneNumbers/views/phone-numbers/update.php` |
| `modules/phoneNumbers/views/phone-numbers/view.php` | phoneNumbers — phone-numbers / view (عرض تفاصيل) (تقديري من الكود) | عرض تفاصيل سجل في phone numbers. | `backend/modules/phoneNumbers/views/phone-numbers/view.php` |
| `modules/profitDistribution/views/profit-distribution/create-portfolio.php` | احتساب أرباح محفظة | واجهة create-portfolio ضمن profit distribution. | `backend/modules/profitDistribution/views/profit-distribution/create-portfolio.php` |
| `modules/profitDistribution/views/profit-distribution/create-shareholders.php` | توزيع أرباح على المساهمين | واجهة create-shareholders ضمن profit distribution. | `backend/modules/profitDistribution/views/profit-distribution/create-shareholders.php` |
| `modules/profitDistribution/views/profit-distribution/index.php` | توزيع الأرباح | عرض وإدارة سجلات profit distribution. | `backend/modules/profitDistribution/views/profit-distribution/index.php` |
| `modules/profitDistribution/views/profit-distribution/view.php` | عرض التوزيع # | عرض تفاصيل سجل في profit distribution. | `backend/modules/profitDistribution/views/profit-distribution/view.php` |
| `modules/realEstate/views/real-estate/create.php` | realEstate — real-estate / create (إنشاء سجل جديد) (تقديري من الكود) | إنشاء سجل جديد في real estate. | `backend/modules/realEstate/views/real-estate/create.php` |
| `modules/realEstate/views/real-estate/index.php` | Real Estates (تقديري من الكود) | عرض وإدارة سجلات real estate. | `backend/modules/realEstate/views/real-estate/index.php` |
| `modules/realEstate/views/real-estate/update.php` | realEstate — real-estate / update (تعديل سجل) (تقديري من الكود) | تعديل سجل موجود في real estate. | `backend/modules/realEstate/views/real-estate/update.php` |
| `modules/realEstate/views/real-estate/view.php` | realEstate — real-estate / view (عرض تفاصيل) (تقديري من الكود) | عرض تفاصيل سجل في real estate. | `backend/modules/realEstate/views/real-estate/view.php` |
| `modules/rejesterFollowUpType/views/rejester-follow-up-type/create.php` | rejesterFollowUpType — rejester-follow-up-type / create (إنشاء سجل جديد) (تقديري من الكود) | إنشاء سجل جديد في rejester follow up type. | `backend/modules/rejesterFollowUpType/views/rejester-follow-up-type/create.php` |
| `modules/rejesterFollowUpType/views/rejester-follow-up-type/index.php` | rejesterFollowUpType — rejester-follow-up-type / index (قائمة / لوحة) (تقديري من الكود) | عرض وإدارة سجلات rejester follow up type. | `backend/modules/rejesterFollowUpType/views/rejester-follow-up-type/index.php` |
| `modules/rejesterFollowUpType/views/rejester-follow-up-type/update.php` | rejesterFollowUpType — rejester-follow-up-type / update (تعديل سجل) (تقديري من الكود) | تعديل سجل موجود في rejester follow up type. | `backend/modules/rejesterFollowUpType/views/rejester-follow-up-type/update.php` |
| `modules/rejesterFollowUpType/views/rejester-follow-up-type/view.php` | rejesterFollowUpType — rejester-follow-up-type / view (عرض تفاصيل) (تقديري من الكود) | عرض تفاصيل سجل في rejester follow up type. | `backend/modules/rejesterFollowUpType/views/rejester-follow-up-type/view.php` |
| `modules/reports/views/customers-judiciary-actions-report/index.php` | الحركات القضائية للعملاء | عرض وإدارة سجلات customers judiciary actions report. | `backend/modules/reports/views/customers-judiciary-actions-report/index.php` |
| `modules/reports/views/customers-judiciary-actions-report/view.php` | reports — customers-judiciary-actions-report / view (عرض تفاصيل) (تقديري من الكود) | عرض تفاصيل سجل في customers judiciary actions report. | `backend/modules/reports/views/customers-judiciary-actions-report/view.php` |
| `modules/reports/views/default/index.php` | context->action->uniqueId ?> (تقديري من الكود) | عرض وإدارة سجلات default. | `backend/modules/reports/views/default/index.php` |
| `modules/reports/views/due_installment.php` | reports — views / due_installment (تقديري من الكود) | واجهة due_installment ضمن views. | `backend/modules/reports/views/due_installment.php` |
| `modules/reports/views/follow-up-reports/index.php` | تقارير المتابعة | عرض وإدارة سجلات follow up reports. | `backend/modules/reports/views/follow-up-reports/index.php` |
| `modules/reports/views/follow-up-reports/view.php` | reports — follow-up-reports / view (عرض تفاصيل) (تقديري من الكود) | عرض تفاصيل سجل في follow up reports. | `backend/modules/reports/views/follow-up-reports/view.php` |
| `modules/reports/views/income-reports/index.php` | reports — income-reports / index (قائمة / لوحة) (تقديري من الكود) | عرض وإدارة سجلات income reports. | `backend/modules/reports/views/income-reports/index.php` |
| `modules/reports/views/income-reports/TotalCustomerPaymentsIndex.php` | تقرير الإيرادات | تقرير أو لوحة إحصاءات مرتبطة بالوحدة. | `backend/modules/reports/views/income-reports/TotalCustomerPaymentsIndex.php` |
| `modules/reports/views/income-reports/TotalJudiciaryCustomerPaymentsIndex.php` | إيرادات القضايا | تقرير أو لوحة إحصاءات مرتبطة بالوحدة. | `backend/modules/reports/views/income-reports/TotalJudiciaryCustomerPaymentsIndex.php` |
| `modules/reports/views/income-reports/view.php` | reports — income-reports / view (عرض تفاصيل) (تقديري من الكود) | عرض تفاصيل سجل في income reports. | `backend/modules/reports/views/income-reports/view.php` |
| `modules/reports/views/index.php` | التقارير | عرض وإدارة سجلات views. | `backend/modules/reports/views/index.php` |
| `modules/reports/views/judiciary/create.php` | reports — judiciary / create (إنشاء سجل جديد) (تقديري من الكود) | إنشاء سجل جديد في judiciary. | `backend/modules/reports/views/judiciary/create.php` |
| `modules/reports/views/judiciary/index.php` | التقارير القضائية | عرض وإدارة سجلات judiciary. | `backend/modules/reports/views/judiciary/index.php` |
| `modules/reports/views/judiciary/report.php` | reports — judiciary / report (تقديري من الكود) | واجهة report ضمن judiciary. | `backend/modules/reports/views/judiciary/report.php` |
| `modules/reports/views/judiciary/update.php` | reports — judiciary / update (تعديل سجل) (تقديري من الكود) | تعديل سجل موجود في judiciary. | `backend/modules/reports/views/judiciary/update.php` |
| `modules/reports/views/judiciary/view.php` | reports — judiciary / view (عرض تفاصيل) (تقديري من الكود) | عرض تفاصيل سجل في judiciary. | `backend/modules/reports/views/judiciary/view.php` |
| `modules/reports/views/monthly_installment.php` | reports — views / monthly_installment (تقديري من الكود) | واجهة monthly_installment ضمن views. | `backend/modules/reports/views/monthly_installment.php` |
| `modules/reports/views/monthly_installment_monthly_beer_user.php` | reports — views / monthly_installment_monthly_beer_user (تقديري من الكود) | واجهة monthly_installment_monthly_beer_user ضمن views. | `backend/modules/reports/views/monthly_installment_monthly_beer_user.php` |
| `modules/reports/views/reports/index.php` | التقارير | عرض وإدارة سجلات reports. | `backend/modules/reports/views/reports/index.php` |
| `modules/reports/views/this_month_installments.php` | reports — views / this_month_installments (تقديري من الكود) | واجهة this_month_installments ضمن views. | `backend/modules/reports/views/this_month_installments.php` |
| `modules/sharedExpenses/views/shared-expense/create.php` | إنشاء توزيع جديد | إنشاء سجل جديد في shared expense. | `backend/modules/sharedExpenses/views/shared-expense/create.php` |
| `modules/sharedExpenses/views/shared-expense/index.php` | توزيع المصاريف المشتركة | عرض وإدارة سجلات shared expense. | `backend/modules/sharedExpenses/views/shared-expense/index.php` |
| `modules/sharedExpenses/views/shared-expense/update.php` | تعديل التوزيع: | تعديل سجل موجود في shared expense. | `backend/modules/sharedExpenses/views/shared-expense/update.php` |
| `modules/sharedExpenses/views/shared-expense/view.php` | عرض التوزيع: | عرض تفاصيل سجل في shared expense. | `backend/modules/sharedExpenses/views/shared-expense/view.php` |
| `modules/shareholders/views/shareholders/create.php` | إضافة مساهم جديد | إنشاء سجل جديد في shareholders. | `backend/modules/shareholders/views/shareholders/create.php` |
| `modules/shareholders/views/shareholders/index.php` | المساهمين | عرض وإدارة سجلات shareholders. | `backend/modules/shareholders/views/shareholders/index.php` |
| `modules/shareholders/views/shareholders/update.php` | تعديل بيانات مساهم: | تعديل سجل موجود في shareholders. | `backend/modules/shareholders/views/shareholders/update.php` |
| `modules/shareholders/views/shareholders/view.php` | عرض المساهم: | عرض تفاصيل سجل في shareholders. | `backend/modules/shareholders/views/shareholders/view.php` |
| `modules/shares/views/shares/create.php` | title) ?> (تقديري من الكود) | إنشاء سجل جديد في shares. | `backend/modules/shares/views/shares/create.php` |
| `modules/shares/views/shares/index.php` | title) ?> (تقديري من الكود) | عرض وإدارة سجلات shares. | `backend/modules/shares/views/shares/index.php` |
| `modules/shares/views/shares/update.php` | title) ?> (تقديري من الكود) | تعديل سجل موجود في shares. | `backend/modules/shares/views/shares/update.php` |
| `modules/shares/views/shares/view.php` | title) ?> (تقديري من الكود) | عرض تفاصيل سجل في shares. | `backend/modules/shares/views/shares/view.php` |
| `modules/sms/views/sms/create.php` | sms — sms / create (إنشاء سجل جديد) (تقديري من الكود) | إنشاء سجل جديد في sms. | `backend/modules/sms/views/sms/create.php` |
| `modules/sms/views/sms/index.php` | Sms (تقديري من الكود) | عرض وإدارة سجلات sms. | `backend/modules/sms/views/sms/index.php` |
| `modules/sms/views/sms/update.php` | sms — sms / update (تعديل سجل) (تقديري من الكود) | تعديل سجل موجود في sms. | `backend/modules/sms/views/sms/update.php` |
| `modules/sms/views/sms/view.php` | sms — sms / view (عرض تفاصيل) (تقديري من الكود) | عرض تفاصيل سجل في sms. | `backend/modules/sms/views/sms/view.php` |
| `modules/status/views/status/create.php` | status — status / create (إنشاء سجل جديد) (تقديري من الكود) | إنشاء سجل جديد في status. | `backend/modules/status/views/status/create.php` |
| `modules/status/views/status/index.php` | Statuses (تقديري من الكود) | عرض وإدارة سجلات status. | `backend/modules/status/views/status/index.php` |
| `modules/status/views/status/update.php` | status — status / update (تعديل سجل) (تقديري من الكود) | تعديل سجل موجود في status. | `backend/modules/status/views/status/update.php` |
| `modules/status/views/status/view.php` | status — status / view (عرض تفاصيل) (تقديري من الكود) | عرض تفاصيل سجل في status. | `backend/modules/status/views/status/view.php` |
| `modules/workdays/views/workdays/create.php` | workdays — workdays / create (إنشاء سجل جديد) (تقديري من الكود) | إنشاء سجل جديد في workdays. | `backend/modules/workdays/views/workdays/create.php` |
| `modules/workdays/views/workdays/index.php` | workdays — workdays / index (قائمة / لوحة) (تقديري من الكود) | عرض وإدارة سجلات workdays. | `backend/modules/workdays/views/workdays/index.php` |
| `modules/workdays/views/workdays/update.php` | workdays — workdays / update (تعديل سجل) (تقديري من الكود) | تعديل سجل موجود في workdays. | `backend/modules/workdays/views/workdays/update.php` |
| `modules/workdays/views/workdays/view.php` | workdays — workdays / view (عرض تفاصيل) (تقديري من الكود) | عرض تفاصيل سجل في workdays. | `backend/modules/workdays/views/workdays/view.php` |
| `views/permissions-management/index.php` | إدارة الصلاحيات | عرض وإدارة سجلات permissions management. | `backend/views/permissions-management/index.php` |
| `views/site/error.php` | حدث خطأ | عرض رسالة خطأ للمستخدم. | `backend/views/site/error.php` |
| `views/site/image-manager.php` | إدارة صور العملاء | واجهة image-manager ضمن site. | `backend/views/site/image-manager.php` |
| `views/site/index.php` | لوحة التحكم | عرض وإدارة سجلات site. | `backend/views/site/index.php` |
| `views/site/system-settings.php` | إعدادات النظام | واجهة system-settings ضمن site. | `backend/views/site/system-settings.php` |
| `views/user-tools/index.php` | أدوات المستخدم | عرض وإدارة سجلات user tools. | `backend/views/user-tools/index.php` |
| `views/user/security/login.php` | تسجيل الدخول | مصادقة المستخدم والدخول للنظام. | `backend/views/user/security/login.php` |
| `views/v.php` | views / v (تقديري من الكود) | واجهة v ضمن views. | `backend/views/v.php` |

---

## ب) جدول الفورمز

| اسم الفورم | النوع | ملف الفورم |
| --- | --- | --- |
|  form (modules/accounting/views/accounts-payable/_form.php) | Form رئيسي | `backend/modules/accounting/views/accounts-payable/_form.php` |
|  form (modules/accounting/views/accounts-receivable/_form.php) | Form رئيسي | `backend/modules/accounting/views/accounts-receivable/_form.php` |
|  form (modules/accounting/views/budget/_form.php) | Form رئيسي | `backend/modules/accounting/views/budget/_form.php` |
| view (modules/accounting/views/budget/view.php) | خاص | `backend/modules/accounting/views/budget/view.php` |
|  form (modules/accounting/views/chart-of-accounts/_form.php) | Form رئيسي | `backend/modules/accounting/views/chart-of-accounts/_form.php` |
|  form (modules/accounting/views/cost-center/_form.php) | Form رئيسي | `backend/modules/accounting/views/cost-center/_form.php` |
|  form (modules/accounting/views/fiscal-year/_form.php) | Form رئيسي | `backend/modules/accounting/views/fiscal-year/_form.php` |
|  form (modules/accounting/views/journal-entry/_form.php) | Form رئيسي | `backend/modules/accounting/views/journal-entry/_form.php` |
|  form (modules/address/views/address/_form.php) | Form رئيسي | `backend/modules/address/views/address/_form.php` |
|  form (modules/attendance/views/attendance/_form.php) | Form رئيسي | `backend/modules/attendance/views/attendance/_form.php` |
|  form (modules/authAssignment/views/auth-assignment/_form.php) | Form رئيسي | `backend/modules/authAssignment/views/auth-assignment/_form.php` |
|  form (modules/bancks/views/bancks/_form.php) | Form رئيسي | `backend/modules/bancks/views/bancks/_form.php` |
|  form (modules/capitalTransactions/views/capital-transactions/_form.php) | Form رئيسي | `backend/modules/capitalTransactions/views/capital-transactions/_form.php` |
|  form (modules/citizen/views/citizen/_form.php) | Form رئيسي | `backend/modules/citizen/views/citizen/_form.php` |
|  form (modules/city/views/city/_form.php) | Form رئيسي | `backend/modules/city/views/city/_form.php` |
|  form (modules/collection/views/collection/_form.php) | Form رئيسي | `backend/modules/collection/views/collection/_form.php` |
|  form (modules/companies/views/companies/_form.php) | Form رئيسي | `backend/modules/companies/views/companies/_form.php` |
|  search (modules/companies/views/companies/_search.php) | Search Form | `backend/modules/companies/views/companies/_search.php` |
|  form (modules/companyBanks/views/company-banks/_form.php) | Form رئيسي | `backend/modules/companyBanks/views/company-banks/_form.php` |
|  form (modules/connectionResponse/views/connection-response/_form.php) | Form رئيسي | `backend/modules/connectionResponse/views/connection-response/_form.php` |
|  form (modules/contactType/views/contact-type/_form.php) | Form رئيسي | `backend/modules/contactType/views/contact-type/_form.php` |
|  form (modules/contractDocumentFile/views/contract-document-file/_form.php) | Form رئيسي | `backend/modules/contractDocumentFile/views/contract-document-file/_form.php` |
|  form (modules/contractInstallment/views/contract-installment/_form.php) | Form رئيسي | `backend/modules/contractInstallment/views/contract-installment/_form.php` |
|  search (modules/contractInstallment/views/contract-installment/_search.php) | Search Form | `backend/modules/contractInstallment/views/contract-installment/_search.php` |
|  form (modules/contracts/views/contracts/_form.php) | Form رئيسي | `backend/modules/contracts/views/contracts/_form.php` |
|  legal department search (modules/contracts/views/contracts/_legal_department_search.php) | Search Form | `backend/modules/contracts/views/contracts/_legal_department_search.php` |
|  legal search v2 (modules/contracts/views/contracts/_legal_search_v2.php) | خاص | `backend/modules/contracts/views/contracts/_legal_search_v2.php` |
|  search (modules/contracts/views/contracts/_search.php) | Search Form | `backend/modules/contracts/views/contracts/_search.php` |
| عقد بيع | خاص | `backend/modules/contracts/views/contracts/print.php` |
|  form (modules/court/views/court/_form.php) | Form رئيسي | `backend/modules/court/views/court/_form.php` |
|  search (modules/court/views/court/_search.php) | Search Form | `backend/modules/court/views/court/_search.php` |
|  form (modules/cousins/views/cousins/_form.php) | Form رئيسي | `backend/modules/cousins/views/cousins/_form.php` |
|  form (modules/customers/views/customers/_form.php) | Form رئيسي | `backend/modules/customers/views/customers/_form.php` |
|  search (modules/customers/views/customers/_search.php) | Search Form | `backend/modules/customers/views/customers/_search.php` |
| إضافة عميل جديد | Form رئيسي | `backend/modules/customers/views/customers/_smart_form.php` |
| contact form (modules/customers/views/customers/contact_form.php) | Form رئيسي | `backend/modules/customers/views/customers/contact_form.php` |
|  account (modules/dektrium/user/views/admin/_account.php) | خاص | `backend/modules/dektrium/user/views/admin/_account.php` |
|  profile (modules/dektrium/user/views/admin/_profile.php) | خاص | `backend/modules/dektrium/user/views/admin/_profile.php` |
| create (modules/dektrium/user/views/admin/create.php) | خاص | `backend/modules/dektrium/user/views/admin/create.php` |
| request (modules/dektrium/user/views/recovery/request.php) | خاص | `backend/modules/dektrium/user/views/recovery/request.php` |
| reset (modules/dektrium/user/views/recovery/reset.php) | خاص | `backend/modules/dektrium/user/views/recovery/reset.php` |
| connect (modules/dektrium/user/views/registration/connect.php) | خاص | `backend/modules/dektrium/user/views/registration/connect.php` |
| register (modules/dektrium/user/views/registration/register.php) | خاص | `backend/modules/dektrium/user/views/registration/register.php` |
| resend (modules/dektrium/user/views/registration/resend.php) | خاص | `backend/modules/dektrium/user/views/registration/resend.php` |
| login (modules/dektrium/user/views/security/login.php) | خاص | `backend/modules/dektrium/user/views/security/login.php` |
| account (modules/dektrium/user/views/settings/account.php) | خاص | `backend/modules/dektrium/user/views/settings/account.php` |
| profile (modules/dektrium/user/views/settings/profile.php) | خاص | `backend/modules/dektrium/user/views/settings/profile.php` |
| login (modules/dektrium/user/widgets/views/login.php) | خاص | `backend/modules/dektrium/user/widgets/views/login.php` |
|  form (modules/department/views/department/_form.php) | Form رئيسي | `backend/modules/department/views/department/_form.php` |
|  form (modules/designation/views/designation/_form.php) | Form رئيسي | `backend/modules/designation/views/designation/_form.php` |
|  form (modules/divisionsCollection/views/divisions-collection/_form.php) | Form رئيسي | `backend/modules/divisionsCollection/views/divisions-collection/_form.php` |
| قسم الديوان | خاص | `backend/modules/diwan/views/diwan/create.php` |
|  form (modules/documentHolder/views/document-holder/_form.php) | Form رئيسي | `backend/modules/documentHolder/views/document-holder/_form.php` |
|  form (modules/documentStatus/views/document-status/_form.php) | Form رئيسي | `backend/modules/documentStatus/views/document-status/_form.php` |
|  form (modules/documentType/views/document-type/_form.php) | Form رئيسي | `backend/modules/documentType/views/document-type/_form.php` |
|  form (modules/employee/views/employee/_form.php) | Form رئيسي | `backend/modules/employee/views/employee/_form.php` |
|  leave policy (modules/employee/views/employee/_leave_policy.php) | خاص | `backend/modules/employee/views/employee/_leave_policy.php` |
|  form (modules/expenseCategories/views/expense-categories/_form.php) | Form رئيسي | `backend/modules/expenseCategories/views/expense-categories/_form.php` |
|  search (modules/expenseCategories/views/expense-categories/_search.php) | Search Form | `backend/modules/expenseCategories/views/expense-categories/_search.php` |
| import (modules/expenseCategories/views/expense-categories/import.php) | خاص | `backend/modules/expenseCategories/views/expense-categories/import.php` |
|  form (modules/expenses/views/expenses/_form.php) | Form رئيسي | `backend/modules/expenses/views/expenses/_form.php` |
|  search (modules/expenses/views/expenses/_search.php) | Search Form | `backend/modules/expenses/views/expenses/_search.php` |
|  form (modules/feelings/views/feelings/_form.php) | Form رئيسي | `backend/modules/feelings/views/feelings/_form.php` |
|  form (modules/financialTransaction/views/financial-transaction/_form.php) | Form رئيسي | `backend/modules/financialTransaction/views/financial-transaction/_form.php` |
|  search (modules/financialTransaction/views/financial-transaction/_search.php) | Search Form | `backend/modules/financialTransaction/views/financial-transaction/_search.php` |
| استيراد كشف حساب بنكي | خاص | `backend/modules/financialTransaction/views/financial-transaction/import.php` |
|  form (modules/followUp/views/follow-up/_form.php) | Form رئيسي | `backend/modules/followUp/views/follow-up/_form.php` |
|  search (modules/followUp/views/follow-up/_search.php) | Search Form | `backend/modules/followUp/views/follow-up/_search.php` |
| phone number form (modules/followUp/views/follow-up/phone_number_form.php) | Form رئيسي | `backend/modules/followUp/views/follow-up/phone_number_form.php` |
|  form (modules/followUpReport/views/follow-up-report/_form.php) | Form رئيسي | `backend/modules/followUpReport/views/follow-up-report/_form.php` |
|  search (modules/followUpReport/views/follow-up-report/_search.php) | Search Form | `backend/modules/followUpReport/views/follow-up-report/_search.php` |
| index (modules/followUpReport/views/follow-up-report/index.php) | خاص | `backend/modules/followUpReport/views/follow-up-report/index.php` |
| عقود بدون أرقام تواصل | خاص | `backend/modules/followUpReport/views/follow-up-report/no-contact.php` |
| عقد بيع | خاص | `backend/modules/followUpReport/views/follow-up-report/print.php` |
|  form (modules/hearAboutUs/views/hear-about-us/_form.php) | Form رئيسي | `backend/modules/hearAboutUs/views/hear-about-us/_form.php` |
|  form (modules/holidays/views/holidays/_form.php) | Form رئيسي | `backend/modules/holidays/views/holidays/_form.php` |
|  search (modules/holidays/views/holidays/_search.php) | Search Form | `backend/modules/holidays/views/holidays/_search.php` |
|  form (modules/hr/views/hr-attendance/_form.php) | Form رئيسي | `backend/modules/hr/views/hr-attendance/_form.php` |
| لوحة الحضور والانصراف | خاص | `backend/modules/hr/views/hr-attendance/index.php` |
|  form (modules/hr/views/hr-employee/_form.php) | Form رئيسي | `backend/modules/hr/views/hr-employee/_form.php` |
| إنشاء مسيرة رواتب جديدة | خاص | `backend/modules/hr/views/hr-payroll/create.php` |
| إنشاء علاوة سنوية جديدة | خاص | `backend/modules/hr/views/hr-payroll/increment-form.php` |
| form (modules/hr/views/hr-shift/form.php) | خاص | `backend/modules/hr/views/hr-shift/form.php` |
| form (modules/hr/views/hr-work-zone/form.php) | خاص | `backend/modules/hr/views/hr-work-zone/form.php` |
|  form (modules/income/views/income/_form.php) | Form رئيسي | `backend/modules/income/views/income/_form.php` |
|  income list search (modules/income/views/income/_income-list-search.php) | خاص | `backend/modules/income/views/income/_income-list-search.php` |
|  search (modules/income/views/income/_search.php) | Search Form | `backend/modules/income/views/income/_search.php` |
| income list form (modules/income/views/income/income_list_form.php) | Form رئيسي | `backend/modules/income/views/income/income_list_form.php` |
|  form (modules/incomeCategory/views/income-category/_form.php) | Form رئيسي | `backend/modules/incomeCategory/views/income-category/_form.php` |
|  form (modules/inventoryInvoices/views/inventory-invoices/_form.php) | Form رئيسي | `backend/modules/inventoryInvoices/views/inventory-invoices/_form.php` |
|  form (modules/inventoryItemQuantities/views/inventory-item-quantities/_form.php) | Form رئيسي | `backend/modules/inventoryItemQuantities/views/inventory-item-quantities/_form.php` |
|  search (modules/inventoryItemQuantities/views/inventory-item-quantities/_search.php) | Search Form | `backend/modules/inventoryItemQuantities/views/inventory-item-quantities/_search.php` |
|  batch form (modules/inventoryItems/views/inventory-items/_batch_form.php) | Form رئيسي | `backend/modules/inventoryItems/views/inventory-items/_batch_form.php` |
|  form (modules/inventoryItems/views/inventory-items/_form.php) | Form رئيسي | `backend/modules/inventoryItems/views/inventory-items/_form.php` |
|  search (modules/inventoryItems/views/inventory-items/_search.php) | Search Form | `backend/modules/inventoryItems/views/inventory-items/_search.php` |
|  serial form (modules/inventoryItems/views/inventory-items/_serial_form.php) | Form رئيسي | `backend/modules/inventoryItems/views/inventory-items/_serial_form.php` |
|  form (modules/inventoryStockLocations/views/inventory-stock-locations/_form.php) | Form رئيسي | `backend/modules/inventoryStockLocations/views/inventory-stock-locations/_form.php` |
|  search (modules/inventoryStockLocations/views/inventory-stock-locations/_search.php) | Search Form | `backend/modules/inventoryStockLocations/views/inventory-stock-locations/_search.php` |
|  form (modules/inventorySuppliers/views/inventory-suppliers/_form.php) | Form رئيسي | `backend/modules/inventorySuppliers/views/inventory-suppliers/_form.php` |
|  search (modules/inventorySuppliers/views/inventory-suppliers/_search.php) | Search Form | `backend/modules/inventorySuppliers/views/inventory-suppliers/_search.php` |
|  form (modules/invoice/views/invoice/_form.php) | Form رئيسي | `backend/modules/invoice/views/invoice/_form.php` |
|  search (modules/invoice/views/invoice/_search.php) | Search Form | `backend/modules/invoice/views/invoice/_search.php` |
|  form (modules/items/views/items/_form.php) | Form رئيسي | `backend/modules/items/views/items/_form.php` |
|  search (modules/items/views/items/_search.php) | Search Form | `backend/modules/items/views/items/_search.php` |
|  form (modules/itemsInventoryInvoices/views/items-inventory-invoices/_form.php) | Form رئيسي | `backend/modules/itemsInventoryInvoices/views/items-inventory-invoices/_form.php` |
|  form (modules/jobs/views/jobs/_form.php) | Form رئيسي | `backend/modules/jobs/views/jobs/_form.php` |
|  search (modules/jobs/views/jobs/_search.php) | Search Form | `backend/modules/jobs/views/jobs/_search.php` |
|  form (modules/judiciary/views/judiciary/_form.php) | Form رئيسي | `backend/modules/judiciary/views/judiciary/_form.php` |
|  search (modules/judiciary/views/judiciary/_search.php) | Search Form | `backend/modules/judiciary/views/judiciary/_search.php` |
|  form (modules/judiciaryActions/views/judiciary-actions/_form.php) | Form رئيسي | `backend/modules/judiciaryActions/views/judiciary-actions/_form.php` |
|  search (modules/judiciaryActions/views/judiciary-actions/_search.php) | Search Form | `backend/modules/judiciaryActions/views/judiciary-actions/_search.php` |
|  form (modules/judiciaryAuthorities/views/judiciary-authorities/_form.php) | Form رئيسي | `backend/modules/judiciaryAuthorities/views/judiciary-authorities/_form.php` |
|  form (modules/judiciaryCustomersActions/views/judiciary-customers-actions/_form.php) | Form رئيسي | `backend/modules/judiciaryCustomersActions/views/judiciary-customers-actions/_form.php` |
|  search (modules/judiciaryCustomersActions/views/judiciary-customers-actions/_search.php) | Search Form | `backend/modules/judiciaryCustomersActions/views/judiciary-customers-actions/_search.php` |
| create in contract (modules/judiciaryCustomersActions/views/judiciary-customers-actions/create-in-contract.php) | خاص | `backend/modules/judiciaryCustomersActions/views/judiciary-customers-actions/create-in-contract.php` |
|  form (modules/JudiciaryInformAddress/views/judiciary-inform-address/_form.php) | Form رئيسي | `backend/modules/JudiciaryInformAddress/views/judiciary-inform-address/_form.php` |
|  search (modules/JudiciaryInformAddress/views/judiciary-inform-address/_search.php) | Search Form | `backend/modules/JudiciaryInformAddress/views/judiciary-inform-address/_search.php` |
|  form (modules/judiciaryRequestTemplates/views/judiciary-request-templates/_form.php) | Form رئيسي | `backend/modules/judiciaryRequestTemplates/views/judiciary-request-templates/_form.php` |
|  form (modules/judiciaryType/views/judiciary-type/_form.php) | Form رئيسي | `backend/modules/judiciaryType/views/judiciary-type/_form.php` |
|  search (modules/judiciaryType/views/judiciary-type/_search.php) | Search Form | `backend/modules/judiciaryType/views/judiciary-type/_search.php` |
|  form (modules/lawyers/views/lawyers/_form.php) | Form رئيسي | `backend/modules/lawyers/views/lawyers/_form.php` |
|  search (modules/lawyers/views/lawyers/_search.php) | Search Form | `backend/modules/lawyers/views/lawyers/_search.php` |
|  form (modules/leavePolicy/views/leave-policy/_form.php) | Form رئيسي | `backend/modules/leavePolicy/views/leave-policy/_form.php` |
|  form (modules/leaveRequest/views/leave-request/_form.php) | Form رئيسي | `backend/modules/leaveRequest/views/leave-request/_form.php` |
|  form (modules/leaveTypes/views/leave-types/_form.php) | Form رئيسي | `backend/modules/leaveTypes/views/leave-types/_form.php` |
|  form follow up (modules/loanScheduling/views/loan-scheduling/_form-follow-up.php) | خاص | `backend/modules/loanScheduling/views/loan-scheduling/_form-follow-up.php` |
|  form (modules/loanScheduling/views/loan-scheduling/_form.php) | Form رئيسي | `backend/modules/loanScheduling/views/loan-scheduling/_form.php` |
|  search (modules/loanScheduling/views/loan-scheduling/_search.php) | Search Form | `backend/modules/loanScheduling/views/loan-scheduling/_search.php` |
|  form (modules/location/views/location/_form.php) | Form رئيسي | `backend/modules/location/views/location/_form.php` |
|  search (modules/location/views/location/_search.php) | Search Form | `backend/modules/location/views/location/_search.php` |
|  form (modules/movment/views/movment/_form.php) | Form رئيسي | `backend/modules/movment/views/movment/_form.php` |
|  search (modules/movment/views/movment/_search.php) | Search Form | `backend/modules/movment/views/movment/_search.php` |
|  form (modules/notification/views/notification/_form.php) | Form رئيسي | `backend/modules/notification/views/notification/_form.php` |
|  search (modules/notification/views/notification/_search.php) | Search Form | `backend/modules/notification/views/notification/_search.php` |
|  form (modules/officialHolidays/views/official-holidays/_form.php) | Form رئيسي | `backend/modules/officialHolidays/views/official-holidays/_form.php` |
|  form (modules/paymentType/views/payment-type/_form.php) | Form رئيسي | `backend/modules/paymentType/views/payment-type/_form.php` |
|  form (modules/phoneNumbers/views/phone-numbers/_form.php) | Form رئيسي | `backend/modules/phoneNumbers/views/phone-numbers/_form.php` |
| احتساب أرباح محفظة | خاص | `backend/modules/profitDistribution/views/profit-distribution/create-portfolio.php` |
| توزيع أرباح على المساهمين | خاص | `backend/modules/profitDistribution/views/profit-distribution/create-shareholders.php` |
|  form (modules/realEstate/views/real-estate/_form.php) | Form رئيسي | `backend/modules/realEstate/views/real-estate/_form.php` |
|  form (modules/rejesterFollowUpType/views/rejester-follow-up-type/_form.php) | Form رئيسي | `backend/modules/rejesterFollowUpType/views/rejester-follow-up-type/_form.php` |
|  search (modules/rejesterFollowUpType/views/rejester-follow-up-type/_search.php) | Search Form | `backend/modules/rejesterFollowUpType/views/rejester-follow-up-type/_search.php` |
|  search (modules/reports/views/customers-judiciary-actions-report/_search.php) | Search Form | `backend/modules/reports/views/customers-judiciary-actions-report/_search.php` |
| الحركات القضائية للعملاء | Search Form | `backend/modules/reports/views/customers-judiciary-actions-report/index.php` |
|  search (modules/reports/views/follow-up-reports/_search.php) | Search Form | `backend/modules/reports/views/follow-up-reports/_search.php` |
| تقارير المتابعة | Search Form | `backend/modules/reports/views/follow-up-reports/index.php` |
|  custamer judiciary search (modules/reports/views/income-reports/_custamer-judiciary-search.php) | خاص | `backend/modules/reports/views/income-reports/_custamer-judiciary-search.php` |
|  custamer search (modules/reports/views/income-reports/_custamer-search.php) | خاص | `backend/modules/reports/views/income-reports/_custamer-search.php` |
| تقرير الإيرادات | Search Form | `backend/modules/reports/views/income-reports/TotalCustomerPaymentsIndex.php` |
| إيرادات القضايا | Search Form | `backend/modules/reports/views/income-reports/TotalJudiciaryCustomerPaymentsIndex.php` |
|  form (modules/reports/views/judiciary/_form.php) | Form رئيسي | `backend/modules/reports/views/judiciary/_form.php` |
|  search (modules/reports/views/judiciary/_search.php) | Search Form | `backend/modules/reports/views/judiciary/_search.php` |
| التقارير القضائية | Search Form | `backend/modules/reports/views/judiciary/index.php` |
|  form (modules/sharedExpenses/views/shared-expense/_form.php) | Form رئيسي | `backend/modules/sharedExpenses/views/shared-expense/_form.php` |
|  form (modules/shareholders/views/shareholders/_form.php) | Form رئيسي | `backend/modules/shareholders/views/shareholders/_form.php` |
|  form (modules/shares/views/shares/_form.php) | Form رئيسي | `backend/modules/shares/views/shares/_form.php` |
|  search (modules/shares/views/shares/_search.php) | Search Form | `backend/modules/shares/views/shares/_search.php` |
|  form (modules/sms/views/sms/_form.php) | Form رئيسي | `backend/modules/sms/views/sms/_form.php` |
|  search (modules/sms/views/sms/_search.php) | Search Form | `backend/modules/sms/views/sms/_search.php` |
|  form (modules/status/views/status/_form.php) | Form رئيسي | `backend/modules/status/views/status/_form.php` |
|  form (modules/workdays/views/workdays/_form.php) | Form رئيسي | `backend/modules/workdays/views/workdays/_form.php` |
| أدوات المستخدم | خاص | `backend/views/user-tools/index.php` |
| تسجيل الدخول | خاص | `backend/views/user/security/login.php` |

---

## ج) جميع ملفات الـ Views

- `backend/modules/accounting/views/accounts-payable/_form.php`
- `backend/modules/accounting/views/accounts-payable/aging-report.php`
- `backend/modules/accounting/views/accounts-payable/create.php`
- `backend/modules/accounting/views/accounts-payable/index.php`
- `backend/modules/accounting/views/accounts-payable/update.php`
- `backend/modules/accounting/views/accounts-receivable/_form.php`
- `backend/modules/accounting/views/accounts-receivable/aging-report.php`
- `backend/modules/accounting/views/accounts-receivable/create.php`
- `backend/modules/accounting/views/accounts-receivable/index.php`
- `backend/modules/accounting/views/accounts-receivable/update.php`
- `backend/modules/accounting/views/ai-insights/index.php`
- `backend/modules/accounting/views/budget/_form.php`
- `backend/modules/accounting/views/budget/create.php`
- `backend/modules/accounting/views/budget/index.php`
- `backend/modules/accounting/views/budget/update.php`
- `backend/modules/accounting/views/budget/variance.php`
- `backend/modules/accounting/views/budget/view.php`
- `backend/modules/accounting/views/chart-of-accounts/_form.php`
- `backend/modules/accounting/views/chart-of-accounts/create.php`
- `backend/modules/accounting/views/chart-of-accounts/index.php`
- `backend/modules/accounting/views/chart-of-accounts/tree.php`
- `backend/modules/accounting/views/chart-of-accounts/update.php`
- `backend/modules/accounting/views/cost-center/_form.php`
- `backend/modules/accounting/views/cost-center/create.php`
- `backend/modules/accounting/views/cost-center/index.php`
- `backend/modules/accounting/views/cost-center/update.php`
- `backend/modules/accounting/views/default/index.php`
- `backend/modules/accounting/views/financial-statements/balance-sheet.php`
- `backend/modules/accounting/views/financial-statements/cash-flow.php`
- `backend/modules/accounting/views/financial-statements/income-statement.php`
- `backend/modules/accounting/views/financial-statements/trial-balance.php`
- `backend/modules/accounting/views/fiscal-year/_form.php`
- `backend/modules/accounting/views/fiscal-year/create.php`
- `backend/modules/accounting/views/fiscal-year/index.php`
- `backend/modules/accounting/views/fiscal-year/update.php`
- `backend/modules/accounting/views/fiscal-year/view.php`
- `backend/modules/accounting/views/general-ledger/account.php`
- `backend/modules/accounting/views/general-ledger/index.php`
- `backend/modules/accounting/views/journal-entry/_form.php`
- `backend/modules/accounting/views/journal-entry/create.php`
- `backend/modules/accounting/views/journal-entry/index.php`
- `backend/modules/accounting/views/journal-entry/update.php`
- `backend/modules/accounting/views/journal-entry/view.php`
- `backend/modules/address/views/address/_columns.php`
- `backend/modules/address/views/address/_form.php`
- `backend/modules/address/views/address/create.php`
- `backend/modules/address/views/address/index.php`
- `backend/modules/address/views/address/update.php`
- `backend/modules/address/views/address/view.php`
- `backend/modules/attendance/views/attendance/_columns.php`
- `backend/modules/attendance/views/attendance/_form.php`
- `backend/modules/attendance/views/attendance/create.php`
- `backend/modules/attendance/views/attendance/index.php`
- `backend/modules/attendance/views/attendance/update.php`
- `backend/modules/attendance/views/attendance/view.php`
- `backend/modules/authAssignment/views/auth-assignment/_columns.php`
- `backend/modules/authAssignment/views/auth-assignment/_form.php`
- `backend/modules/authAssignment/views/auth-assignment/create.php`
- `backend/modules/authAssignment/views/auth-assignment/index.php`
- `backend/modules/authAssignment/views/auth-assignment/update.php`
- `backend/modules/authAssignment/views/auth-assignment/view.php`
- `backend/modules/bancks/views/bancks/_columns.php`
- `backend/modules/bancks/views/bancks/_form.php`
- `backend/modules/bancks/views/bancks/create.php`
- `backend/modules/bancks/views/bancks/index.php`
- `backend/modules/bancks/views/bancks/update.php`
- `backend/modules/bancks/views/bancks/view.php`
- `backend/modules/capitalTransactions/views/capital-transactions/_form.php`
- `backend/modules/capitalTransactions/views/capital-transactions/create.php`
- `backend/modules/capitalTransactions/views/capital-transactions/index.php`
- `backend/modules/capitalTransactions/views/capital-transactions/update.php`
- `backend/modules/capitalTransactions/views/capital-transactions/view.php`
- `backend/modules/citizen/views/citizen/_columns.php`
- `backend/modules/citizen/views/citizen/_form.php`
- `backend/modules/citizen/views/citizen/create.php`
- `backend/modules/citizen/views/citizen/index.php`
- `backend/modules/citizen/views/citizen/update.php`
- `backend/modules/citizen/views/citizen/view.php`
- `backend/modules/city/views/city/_columns.php`
- `backend/modules/city/views/city/_form.php`
- `backend/modules/city/views/city/create.php`
- `backend/modules/city/views/city/index.php`
- `backend/modules/city/views/city/update.php`
- `backend/modules/city/views/city/view.php`
- `backend/modules/collection/views/collection/_columns.php`
- `backend/modules/collection/views/collection/_form.php`
- `backend/modules/collection/views/collection/create.php`
- `backend/modules/collection/views/collection/index.php`
- `backend/modules/collection/views/collection/update.php`
- `backend/modules/collection/views/collection/view.php`
- `backend/modules/companies/views/companies/_columns.php`
- `backend/modules/companies/views/companies/_form.php`
- `backend/modules/companies/views/companies/_parital/company_banks.php`
- `backend/modules/companies/views/companies/_search.php`
- `backend/modules/companies/views/companies/create.php`
- `backend/modules/companies/views/companies/index.php`
- `backend/modules/companies/views/companies/update.php`
- `backend/modules/companies/views/companies/view.php`
- `backend/modules/companyBanks/views/company-banks/_columns.php`
- `backend/modules/companyBanks/views/company-banks/_form.php`
- `backend/modules/companyBanks/views/company-banks/create.php`
- `backend/modules/companyBanks/views/company-banks/index.php`
- `backend/modules/companyBanks/views/company-banks/update.php`
- `backend/modules/companyBanks/views/company-banks/view.php`
- `backend/modules/companyBanks/views/default/index.php`
- `backend/modules/connectionResponse/views/connection-response/_columns.php`
- `backend/modules/connectionResponse/views/connection-response/_form.php`
- `backend/modules/connectionResponse/views/connection-response/create.php`
- `backend/modules/connectionResponse/views/connection-response/index.php`
- `backend/modules/connectionResponse/views/connection-response/update.php`
- `backend/modules/connectionResponse/views/connection-response/view.php`
- `backend/modules/contactType/views/contact-type/_columns.php`
- `backend/modules/contactType/views/contact-type/_form.php`
- `backend/modules/contactType/views/contact-type/create.php`
- `backend/modules/contactType/views/contact-type/index.php`
- `backend/modules/contactType/views/contact-type/update.php`
- `backend/modules/contactType/views/contact-type/view.php`
- `backend/modules/contractDocumentFile/views/contract-document-file/_columns.php`
- `backend/modules/contractDocumentFile/views/contract-document-file/_form.php`
- `backend/modules/contractDocumentFile/views/contract-document-file/create.php`
- `backend/modules/contractDocumentFile/views/contract-document-file/index.php`
- `backend/modules/contractDocumentFile/views/contract-document-file/update.php`
- `backend/modules/contractDocumentFile/views/contract-document-file/view.php`
- `backend/modules/contractInstallment/views/contract-installment/_form.php`
- `backend/modules/contractInstallment/views/contract-installment/_search.php`
- `backend/modules/contractInstallment/views/contract-installment/create.php`
- `backend/modules/contractInstallment/views/contract-installment/index.php`
- `backend/modules/contractInstallment/views/contract-installment/print.php`
- `backend/modules/contractInstallment/views/contract-installment/update.php`
- `backend/modules/contractInstallment/views/contract-installment/verify-receipt.php`
- `backend/modules/contractInstallment/views/contract-installment/view.php`
- `backend/modules/contracts/views/contracts/_adjustments.php`
- `backend/modules/contracts/views/contracts/_columns.php`
- `backend/modules/contracts/views/contracts/_contract_print.php`
- `backend/modules/contracts/views/contracts/_draft_print.php`
- `backend/modules/contracts/views/contracts/_form.php`
- `backend/modules/contracts/views/contracts/_legal_columns.php`
- `backend/modules/contracts/views/contracts/_legal_department_search.php`
- `backend/modules/contracts/views/contracts/_legal_search_v2.php`
- `backend/modules/contracts/views/contracts/_print_overlay.php`
- `backend/modules/contracts/views/contracts/_print_preview.php`
- `backend/modules/contracts/views/contracts/_search.php`
- `backend/modules/contracts/views/contracts/create.php`
- `backend/modules/contracts/views/contracts/first_page.php`
- `backend/modules/contracts/views/contracts/index-legal-department.php`
- `backend/modules/contracts/views/contracts/index.php`
- `backend/modules/contracts/views/contracts/print.php`
- `backend/modules/contracts/views/contracts/second_page.php`
- `backend/modules/contracts/views/contracts/update.php`
- `backend/modules/contracts/views/contracts/view.php`
- `backend/modules/court/views/court/_columns.php`
- `backend/modules/court/views/court/_form.php`
- `backend/modules/court/views/court/_search.php`
- `backend/modules/court/views/court/create.php`
- `backend/modules/court/views/court/index.php`
- `backend/modules/court/views/court/update.php`
- `backend/modules/court/views/court/view.php`
- `backend/modules/cousins/views/cousins/_columns.php`
- `backend/modules/cousins/views/cousins/_form.php`
- `backend/modules/cousins/views/cousins/create.php`
- `backend/modules/cousins/views/cousins/index.php`
- `backend/modules/cousins/views/cousins/update.php`
- `backend/modules/cousins/views/cousins/view.php`
- `backend/modules/customers/views/customers/_columns.php`
- `backend/modules/customers/views/customers/_form.php`
- `backend/modules/customers/views/customers/_search.php`
- `backend/modules/customers/views/customers/_smart_form.php`
- `backend/modules/customers/views/customers/contact_form.php`
- `backend/modules/customers/views/customers/contact_update.php`
- `backend/modules/customers/views/customers/create-summary.php`
- `backend/modules/customers/views/customers/create.php`
- `backend/modules/customers/views/customers/index.php`
- `backend/modules/customers/views/customers/partial/address.php`
- `backend/modules/customers/views/customers/partial/customer_documents.php`
- `backend/modules/customers/views/customers/partial/phone_numbers.php`
- `backend/modules/customers/views/customers/partial/real_estate.php`
- `backend/modules/customers/views/customers/update.php`
- `backend/modules/customers/views/customers/view.php`
- `backend/modules/dektrium/user/views/_alert.php`
- `backend/modules/dektrium/user/views/admin/_account.php`
- `backend/modules/dektrium/user/views/admin/_assignments.php`
- `backend/modules/dektrium/user/views/admin/_info.php`
- `backend/modules/dektrium/user/views/admin/_menu.php`
- `backend/modules/dektrium/user/views/admin/_profile.php`
- `backend/modules/dektrium/user/views/admin/_user.php`
- `backend/modules/dektrium/user/views/admin/create.php`
- `backend/modules/dektrium/user/views/admin/index.php`
- `backend/modules/dektrium/user/views/admin/update.php`
- `backend/modules/dektrium/user/views/mail/confirmation.php`
- `backend/modules/dektrium/user/views/mail/layouts/html.php`
- `backend/modules/dektrium/user/views/mail/layouts/text.php`
- `backend/modules/dektrium/user/views/mail/new_password.php`
- `backend/modules/dektrium/user/views/mail/reconfirmation.php`
- `backend/modules/dektrium/user/views/mail/recovery.php`
- `backend/modules/dektrium/user/views/mail/text/confirmation.php`
- `backend/modules/dektrium/user/views/mail/text/new_password.php`
- `backend/modules/dektrium/user/views/mail/text/reconfirmation.php`
- `backend/modules/dektrium/user/views/mail/text/recovery.php`
- `backend/modules/dektrium/user/views/mail/text/welcome.php`
- `backend/modules/dektrium/user/views/mail/welcome.php`
- `backend/modules/dektrium/user/views/message.php`
- `backend/modules/dektrium/user/views/profile/show.php`
- `backend/modules/dektrium/user/views/recovery/request.php`
- `backend/modules/dektrium/user/views/recovery/reset.php`
- `backend/modules/dektrium/user/views/registration/connect.php`
- `backend/modules/dektrium/user/views/registration/register.php`
- `backend/modules/dektrium/user/views/registration/resend.php`
- `backend/modules/dektrium/user/views/security/login.php`
- `backend/modules/dektrium/user/views/settings/_menu.php`
- `backend/modules/dektrium/user/views/settings/account.php`
- `backend/modules/dektrium/user/views/settings/networks.php`
- `backend/modules/dektrium/user/views/settings/profile.php`
- `backend/modules/dektrium/user/widgets/views/login.php`
- `backend/modules/department/views/department/_columns.php`
- `backend/modules/department/views/department/_form.php`
- `backend/modules/department/views/department/create.php`
- `backend/modules/department/views/department/index.php`
- `backend/modules/department/views/department/update.php`
- `backend/modules/department/views/department/view.php`
- `backend/modules/designation/views/designation/_columns.php`
- `backend/modules/designation/views/designation/_form.php`
- `backend/modules/designation/views/designation/create.php`
- `backend/modules/designation/views/designation/index.php`
- `backend/modules/designation/views/designation/update.php`
- `backend/modules/designation/views/designation/view.php`
- `backend/modules/divisionsCollection/views/default/index.php`
- `backend/modules/divisionsCollection/views/divisions-collection/_columns.php`
- `backend/modules/divisionsCollection/views/divisions-collection/_form.php`
- `backend/modules/divisionsCollection/views/divisions-collection/create.php`
- `backend/modules/divisionsCollection/views/divisions-collection/index.php`
- `backend/modules/divisionsCollection/views/divisions-collection/update.php`
- `backend/modules/divisionsCollection/views/divisions-collection/view.php`
- `backend/modules/diwan/views/diwan/correspondence_index.php`
- `backend/modules/diwan/views/diwan/correspondence_view.php`
- `backend/modules/diwan/views/diwan/create.php`
- `backend/modules/diwan/views/diwan/document_history.php`
- `backend/modules/diwan/views/diwan/index.php`
- `backend/modules/diwan/views/diwan/receipt.php`
- `backend/modules/diwan/views/diwan/reports.php`
- `backend/modules/diwan/views/diwan/search.php`
- `backend/modules/diwan/views/diwan/transactions.php`
- `backend/modules/diwan/views/diwan/view.php`
- `backend/modules/documentHolder/views/document-holder/_archives_columns.php`
- `backend/modules/documentHolder/views/document-holder/_columns.php`
- `backend/modules/documentHolder/views/document-holder/_form.php`
- `backend/modules/documentHolder/views/document-holder/_manager_column.php`
- `backend/modules/documentHolder/views/document-holder/archives.php`
- `backend/modules/documentHolder/views/document-holder/create.php`
- `backend/modules/documentHolder/views/document-holder/index.php`
- `backend/modules/documentHolder/views/document-holder/manager_index.php`
- `backend/modules/documentHolder/views/document-holder/update.php`
- `backend/modules/documentHolder/views/document-holder/view.php`
- `backend/modules/documentStatus/views/document-status/_columns.php`
- `backend/modules/documentStatus/views/document-status/_form.php`
- `backend/modules/documentStatus/views/document-status/create.php`
- `backend/modules/documentStatus/views/document-status/index.php`
- `backend/modules/documentStatus/views/document-status/update.php`
- `backend/modules/documentStatus/views/document-status/view.php`
- `backend/modules/documentType/views/document-type/_columns.php`
- `backend/modules/documentType/views/document-type/_form.php`
- `backend/modules/documentType/views/document-type/create.php`
- `backend/modules/documentType/views/document-type/index.php`
- `backend/modules/documentType/views/document-type/update.php`
- `backend/modules/documentType/views/document-type/view.php`
- `backend/modules/employee/views/employee/_columns.php`
- `backend/modules/employee/views/employee/_form.php`
- `backend/modules/employee/views/employee/_leave_policy.php`
- `backend/modules/employee/views/employee/_partial/_attachments_table.php`
- `backend/modules/employee/views/employee/create.php`
- `backend/modules/employee/views/employee/index.php`
- `backend/modules/employee/views/employee/update.php`
- `backend/modules/employee/views/employee/view.php`
- `backend/modules/expenseCategories/views/expense-categories/_columns.php`
- `backend/modules/expenseCategories/views/expense-categories/_form.php`
- `backend/modules/expenseCategories/views/expense-categories/_search.php`
- `backend/modules/expenseCategories/views/expense-categories/create.php`
- `backend/modules/expenseCategories/views/expense-categories/import.php`
- `backend/modules/expenseCategories/views/expense-categories/index.php`
- `backend/modules/expenseCategories/views/expense-categories/update.php`
- `backend/modules/expenseCategories/views/expense-categories/view.php`
- `backend/modules/expenses/views/expenses/_columns.php`
- `backend/modules/expenses/views/expenses/_form.php`
- `backend/modules/expenses/views/expenses/_search.php`
- `backend/modules/expenses/views/expenses/create.php`
- `backend/modules/expenses/views/expenses/index.php`
- `backend/modules/expenses/views/expenses/update.php`
- `backend/modules/expenses/views/expenses/view.php`
- `backend/modules/feelings/views/feelings/_columns.php`
- `backend/modules/feelings/views/feelings/_form.php`
- `backend/modules/feelings/views/feelings/create.php`
- `backend/modules/feelings/views/feelings/index.php`
- `backend/modules/feelings/views/feelings/update.php`
- `backend/modules/feelings/views/feelings/view.php`
- `backend/modules/financialTransaction/views/financial-transaction/_columns.php`
- `backend/modules/financialTransaction/views/financial-transaction/_form.php`
- `backend/modules/financialTransaction/views/financial-transaction/_search.php`
- `backend/modules/financialTransaction/views/financial-transaction/create.php`
- `backend/modules/financialTransaction/views/financial-transaction/import.php`
- `backend/modules/financialTransaction/views/financial-transaction/import_grid_view.php`
- `backend/modules/financialTransaction/views/financial-transaction/index.php`
- `backend/modules/financialTransaction/views/financial-transaction/update.php`
- `backend/modules/financialTransaction/views/financial-transaction/view.php`
- `backend/modules/followUp/views/follow-up/_columns.php`
- `backend/modules/followUp/views/follow-up/_form.php`
- `backend/modules/followUp/views/follow-up/_search.php`
- `backend/modules/followUp/views/follow-up/clearance.php`
- `backend/modules/followUp/views/follow-up/create.php`
- `backend/modules/followUp/views/follow-up/index.php`
- `backend/modules/followUp/views/follow-up/modals.php`
- `backend/modules/followUp/views/follow-up/panel.php`
- `backend/modules/followUp/views/follow-up/panel/_ai_suggestions.php`
- `backend/modules/followUp/views/follow-up/panel/_financial.php`
- `backend/modules/followUp/views/follow-up/panel/_judiciary_tab.php`
- `backend/modules/followUp/views/follow-up/panel/_kanban.php`
- `backend/modules/followUp/views/follow-up/panel/_side_panels.php`
- `backend/modules/followUp/views/follow-up/panel/_timeline.php`
- `backend/modules/followUp/views/follow-up/partial/follow-up-view.php`
- `backend/modules/followUp/views/follow-up/partial/next_contract.php`
- `backend/modules/followUp/views/follow-up/partial/phone_numbers_follow_up.php`
- `backend/modules/followUp/views/follow-up/partial/tabs.php`
- `backend/modules/followUp/views/follow-up/partial/tabs/actions.php`
- `backend/modules/followUp/views/follow-up/partial/tabs/financial.php`
- `backend/modules/followUp/views/follow-up/partial/tabs/judiciary_customers_actions.php`
- `backend/modules/followUp/views/follow-up/partial/tabs/loan_scheduling.php`
- `backend/modules/followUp/views/follow-up/partial/tabs/payments.php`
- `backend/modules/followUp/views/follow-up/partial/tabs/phone_numbers.php`
- `backend/modules/followUp/views/follow-up/phone_number_create.php`
- `backend/modules/followUp/views/follow-up/phone_number_form.php`
- `backend/modules/followUp/views/follow-up/phone_number_update.php`
- `backend/modules/followUp/views/follow-up/printer.php`
- `backend/modules/followUp/views/follow-up/tabs.php`
- `backend/modules/followUp/views/follow-up/update.php`
- `backend/modules/followUp/views/follow-up/verify-statement.php`
- `backend/modules/followUp/views/follow-up/view.php`
- `backend/modules/followUpReport/views/follow-up-report/_columns.php`
- `backend/modules/followUpReport/views/follow-up-report/_form.php`
- `backend/modules/followUpReport/views/follow-up-report/_search.php`
- `backend/modules/followUpReport/views/follow-up-report/create.php`
- `backend/modules/followUpReport/views/follow-up-report/first_page.php`
- `backend/modules/followUpReport/views/follow-up-report/index.php`
- `backend/modules/followUpReport/views/follow-up-report/no-contact.php`
- `backend/modules/followUpReport/views/follow-up-report/print.php`
- `backend/modules/followUpReport/views/follow-up-report/update.php`
- `backend/modules/followUpReport/views/follow-up-report/view.php`
- `backend/modules/hearAboutUs/views/hear-about-us/_columns.php`
- `backend/modules/hearAboutUs/views/hear-about-us/_form.php`
- `backend/modules/hearAboutUs/views/hear-about-us/create.php`
- `backend/modules/hearAboutUs/views/hear-about-us/index.php`
- `backend/modules/hearAboutUs/views/hear-about-us/update.php`
- `backend/modules/hearAboutUs/views/hear-about-us/view.php`
- `backend/modules/holidays/views/holidays/_columns.php`
- `backend/modules/holidays/views/holidays/_form.php`
- `backend/modules/holidays/views/holidays/_search.php`
- `backend/modules/holidays/views/holidays/create.php`
- `backend/modules/holidays/views/holidays/index.php`
- `backend/modules/holidays/views/holidays/update.php`
- `backend/modules/holidays/views/holidays/view.php`
- `backend/modules/hr/views/_section_tabs.php`
- `backend/modules/hr/views/hr-attendance/_form.php`
- `backend/modules/hr/views/hr-attendance/create.php`
- `backend/modules/hr/views/hr-attendance/index.php`
- `backend/modules/hr/views/hr-attendance/summary.php`
- `backend/modules/hr/views/hr-dashboard/index.php`
- `backend/modules/hr/views/hr-employee/_form.php`
- `backend/modules/hr/views/hr-employee/create.php`
- `backend/modules/hr/views/hr-employee/index.php`
- `backend/modules/hr/views/hr-employee/statement.php`
- `backend/modules/hr/views/hr-employee/update.php`
- `backend/modules/hr/views/hr-employee/view.php`
- `backend/modules/hr/views/hr-evaluation/index.php`
- `backend/modules/hr/views/hr-field/index.php`
- `backend/modules/hr/views/hr-field/map.php`
- `backend/modules/hr/views/hr-field/mobile-login.php`
- `backend/modules/hr/views/hr-field/mobile.php`
- `backend/modules/hr/views/hr-leave/index.php`
- `backend/modules/hr/views/hr-loan/index.php`
- `backend/modules/hr/views/hr-payroll/adjustments.php`
- `backend/modules/hr/views/hr-payroll/components.php`
- `backend/modules/hr/views/hr-payroll/create.php`
- `backend/modules/hr/views/hr-payroll/increment-bulk-preview.php`
- `backend/modules/hr/views/hr-payroll/increment-bulk.php`
- `backend/modules/hr/views/hr-payroll/increment-form.php`
- `backend/modules/hr/views/hr-payroll/increments.php`
- `backend/modules/hr/views/hr-payroll/index.php`
- `backend/modules/hr/views/hr-payroll/payslip.php`
- `backend/modules/hr/views/hr-payroll/view.php`
- `backend/modules/hr/views/hr-report/index.php`
- `backend/modules/hr/views/hr-shift/form.php`
- `backend/modules/hr/views/hr-shift/index.php`
- `backend/modules/hr/views/hr-tracking-api/attendance-board.php`
- `backend/modules/hr/views/hr-tracking-api/live-map.php`
- `backend/modules/hr/views/hr-tracking-api/mobile-attendance.php`
- `backend/modules/hr/views/hr-tracking-api/mobile-login.php`
- `backend/modules/hr/views/hr-tracking-report/index.php`
- `backend/modules/hr/views/hr-tracking-report/monthly.php`
- `backend/modules/hr/views/hr-tracking-report/punctuality.php`
- `backend/modules/hr/views/hr-tracking-report/violations.php`
- `backend/modules/hr/views/hr-work-zone/form.php`
- `backend/modules/hr/views/hr-work-zone/index.php`
- `backend/modules/income/views/income/_columns.php`
- `backend/modules/income/views/income/_form.php`
- `backend/modules/income/views/income/_income-list-columns.php`
- `backend/modules/income/views/income/_income-list-search.php`
- `backend/modules/income/views/income/_search.php`
- `backend/modules/income/views/income/create.php`
- `backend/modules/income/views/income/income-item-list.php`
- `backend/modules/income/views/income/income_list_form.php`
- `backend/modules/income/views/income/index.php`
- `backend/modules/income/views/income/update.php`
- `backend/modules/income/views/income/view.php`
- `backend/modules/incomeCategory/views/income-category/_columns.php`
- `backend/modules/incomeCategory/views/income-category/_form.php`
- `backend/modules/incomeCategory/views/income-category/create.php`
- `backend/modules/incomeCategory/views/income-category/index.php`
- `backend/modules/incomeCategory/views/income-category/update.php`
- `backend/modules/incomeCategory/views/income-category/view.php`
- `backend/modules/inventoryInvoices/views/default/index.php`
- `backend/modules/inventoryInvoices/views/inventory-invoices/_columns.php`
- `backend/modules/inventoryInvoices/views/inventory-invoices/_form.php`
- `backend/modules/inventoryInvoices/views/inventory-invoices/_items_inventory_invoices.php`
- `backend/modules/inventoryInvoices/views/inventory-invoices/create-wizard.php`
- `backend/modules/inventoryInvoices/views/inventory-invoices/create.php`
- `backend/modules/inventoryInvoices/views/inventory-invoices/index.php`
- `backend/modules/inventoryInvoices/views/inventory-invoices/reject-reception.php`
- `backend/modules/inventoryInvoices/views/inventory-invoices/update.php`
- `backend/modules/inventoryInvoices/views/inventory-invoices/view.php`
- `backend/modules/inventoryItemQuantities/views/inventory-item-quantities/_columns.php`
- `backend/modules/inventoryItemQuantities/views/inventory-item-quantities/_form.php`
- `backend/modules/inventoryItemQuantities/views/inventory-item-quantities/_search.php`
- `backend/modules/inventoryItemQuantities/views/inventory-item-quantities/create.php`
- `backend/modules/inventoryItemQuantities/views/inventory-item-quantities/index.php`
- `backend/modules/inventoryItemQuantities/views/inventory-item-quantities/update.php`
- `backend/modules/inventoryItemQuantities/views/inventory-item-quantities/view.php`
- `backend/modules/inventoryItems/views/inventory-items/_batch_form.php`
- `backend/modules/inventoryItems/views/inventory-items/_columns.php`
- `backend/modules/inventoryItems/views/inventory-items/_columns_item_query.php`
- `backend/modules/inventoryItems/views/inventory-items/_form.php`
- `backend/modules/inventoryItems/views/inventory-items/_search.php`
- `backend/modules/inventoryItems/views/inventory-items/_serial_columns.php`
- `backend/modules/inventoryItems/views/inventory-items/_serial_form.php`
- `backend/modules/inventoryItems/views/inventory-items/_serial_view.php`
- `backend/modules/inventoryItems/views/inventory-items/create.php`
- `backend/modules/inventoryItems/views/inventory-items/dashboard.php`
- `backend/modules/inventoryItems/views/inventory-items/index.php`
- `backend/modules/inventoryItems/views/inventory-items/index_item_query.php`
- `backend/modules/inventoryItems/views/inventory-items/items.php`
- `backend/modules/inventoryItems/views/inventory-items/movements.php`
- `backend/modules/inventoryItems/views/inventory-items/serial-numbers.php`
- `backend/modules/inventoryItems/views/inventory-items/settings.php`
- `backend/modules/inventoryItems/views/inventory-items/update.php`
- `backend/modules/inventoryItems/views/inventory-items/view.php`
- `backend/modules/inventoryStockLocations/views/inventory-stock-locations/_columns.php`
- `backend/modules/inventoryStockLocations/views/inventory-stock-locations/_form.php`
- `backend/modules/inventoryStockLocations/views/inventory-stock-locations/_search.php`
- `backend/modules/inventoryStockLocations/views/inventory-stock-locations/create.php`
- `backend/modules/inventoryStockLocations/views/inventory-stock-locations/index.php`
- `backend/modules/inventoryStockLocations/views/inventory-stock-locations/update.php`
- `backend/modules/inventoryStockLocations/views/inventory-stock-locations/view.php`
- `backend/modules/inventorySuppliers/views/inventory-suppliers/_columns.php`
- `backend/modules/inventorySuppliers/views/inventory-suppliers/_form.php`
- `backend/modules/inventorySuppliers/views/inventory-suppliers/_search.php`
- `backend/modules/inventorySuppliers/views/inventory-suppliers/create.php`
- `backend/modules/inventorySuppliers/views/inventory-suppliers/index.php`
- `backend/modules/inventorySuppliers/views/inventory-suppliers/update.php`
- `backend/modules/inventorySuppliers/views/inventory-suppliers/view.php`
- `backend/modules/invoice/views/invoice/_columns.php`
- `backend/modules/invoice/views/invoice/_customer.php`
- `backend/modules/invoice/views/invoice/_form.php`
- `backend/modules/invoice/views/invoice/_search.php`
- `backend/modules/invoice/views/invoice/create.php`
- `backend/modules/invoice/views/invoice/index.php`
- `backend/modules/invoice/views/invoice/update.php`
- `backend/modules/invoice/views/invoice/view.php`
- `backend/modules/items/views/items/_columns.php`
- `backend/modules/items/views/items/_form.php`
- `backend/modules/items/views/items/_search.php`
- `backend/modules/items/views/items/create.php`
- `backend/modules/items/views/items/index.php`
- `backend/modules/items/views/items/update.php`
- `backend/modules/items/views/items/view.php`
- `backend/modules/itemsInventoryInvoices/views/default/index.php`
- `backend/modules/itemsInventoryInvoices/views/items-inventory-invoices/_columns.php`
- `backend/modules/itemsInventoryInvoices/views/items-inventory-invoices/_form.php`
- `backend/modules/itemsInventoryInvoices/views/items-inventory-invoices/create.php`
- `backend/modules/itemsInventoryInvoices/views/items-inventory-invoices/index.php`
- `backend/modules/itemsInventoryInvoices/views/items-inventory-invoices/update.php`
- `backend/modules/itemsInventoryInvoices/views/items-inventory-invoices/view.php`
- `backend/modules/jobs/views/jobs/_columns.php`
- `backend/modules/jobs/views/jobs/_form.php`
- `backend/modules/jobs/views/jobs/_search.php`
- `backend/modules/jobs/views/jobs/create.php`
- `backend/modules/jobs/views/jobs/index.php`
- `backend/modules/jobs/views/jobs/update.php`
- `backend/modules/jobs/views/jobs/view.php`
- `backend/modules/judiciary/views/judiciary/_columns.php`
- `backend/modules/judiciary/views/judiciary/_form.php`
- `backend/modules/judiciary/views/judiciary/_report_columns.php`
- `backend/modules/judiciary/views/judiciary/_search.php`
- `backend/modules/judiciary/views/judiciary/_tab_actions.php`
- `backend/modules/judiciary/views/judiciary/_tab_cases.php`
- `backend/modules/judiciary/views/judiciary/_tab_collection.php`
- `backend/modules/judiciary/views/judiciary/_tab_legal.php`
- `backend/modules/judiciary/views/judiciary/_tab_persistence.php`
- `backend/modules/judiciary/views/judiciary/batch_actions.php`
- `backend/modules/judiciary/views/judiciary/batch_create.php`
- `backend/modules/judiciary/views/judiciary/batch_print.php`
- `backend/modules/judiciary/views/judiciary/cases_report.php`
- `backend/modules/judiciary/views/judiciary/cases_report_print.php`
- `backend/modules/judiciary/views/judiciary/create.php`
- `backend/modules/judiciary/views/judiciary/deadline_dashboard.php`
- `backend/modules/judiciary/views/judiciary/generate_request.php`
- `backend/modules/judiciary/views/judiciary/index.php`
- `backend/modules/judiciary/views/judiciary/print_case.php`
- `backend/modules/judiciary/views/judiciary/report.php`
- `backend/modules/judiciary/views/judiciary/update.php`
- `backend/modules/judiciary/views/judiciary/view.php`
- `backend/modules/judiciaryActions/views/judiciary-actions/_columns.php`
- `backend/modules/judiciaryActions/views/judiciary-actions/_confirm_delete.php`
- `backend/modules/judiciaryActions/views/judiciary-actions/_form.php`
- `backend/modules/judiciaryActions/views/judiciary-actions/_search.php`
- `backend/modules/judiciaryActions/views/judiciary-actions/_usage_details.php`
- `backend/modules/judiciaryActions/views/judiciary-actions/create.php`
- `backend/modules/judiciaryActions/views/judiciary-actions/index.php`
- `backend/modules/judiciaryActions/views/judiciary-actions/update.php`
- `backend/modules/judiciaryActions/views/judiciary-actions/view.php`
- `backend/modules/judiciaryAuthorities/views/judiciary-authorities/_columns.php`
- `backend/modules/judiciaryAuthorities/views/judiciary-authorities/_form.php`
- `backend/modules/judiciaryAuthorities/views/judiciary-authorities/create.php`
- `backend/modules/judiciaryAuthorities/views/judiciary-authorities/index.php`
- `backend/modules/judiciaryAuthorities/views/judiciary-authorities/update.php`
- `backend/modules/judiciaryAuthorities/views/judiciary-authorities/view.php`
- `backend/modules/judiciaryCustomersActions/views/judiciary-customers-actions/_columns.php`
- `backend/modules/judiciaryCustomersActions/views/judiciary-customers-actions/_form.php`
- `backend/modules/judiciaryCustomersActions/views/judiciary-customers-actions/_search.php`
- `backend/modules/judiciaryCustomersActions/views/judiciary-customers-actions/_select_judiciary.php`
- `backend/modules/judiciaryCustomersActions/views/judiciary-customers-actions/create-in-contract.php`
- `backend/modules/judiciaryCustomersActions/views/judiciary-customers-actions/create.php`
- `backend/modules/judiciaryCustomersActions/views/judiciary-customers-actions/index.php`
- `backend/modules/judiciaryCustomersActions/views/judiciary-customers-actions/update.php`
- `backend/modules/judiciaryCustomersActions/views/judiciary-customers-actions/view.php`
- `backend/modules/JudiciaryInformAddress/views/judiciary-inform-address/_form.php`
- `backend/modules/JudiciaryInformAddress/views/judiciary-inform-address/_search.php`
- `backend/modules/JudiciaryInformAddress/views/judiciary-inform-address/create.php`
- `backend/modules/JudiciaryInformAddress/views/judiciary-inform-address/index.php`
- `backend/modules/JudiciaryInformAddress/views/judiciary-inform-address/update.php`
- `backend/modules/JudiciaryInformAddress/views/judiciary-inform-address/view.php`
- `backend/modules/judiciaryRequestTemplates/views/judiciary-request-templates/_columns.php`
- `backend/modules/judiciaryRequestTemplates/views/judiciary-request-templates/_form.php`
- `backend/modules/judiciaryRequestTemplates/views/judiciary-request-templates/create.php`
- `backend/modules/judiciaryRequestTemplates/views/judiciary-request-templates/index.php`
- `backend/modules/judiciaryRequestTemplates/views/judiciary-request-templates/update.php`
- `backend/modules/judiciaryRequestTemplates/views/judiciary-request-templates/view.php`
- `backend/modules/judiciaryType/views/judiciary-type/_columns.php`
- `backend/modules/judiciaryType/views/judiciary-type/_form.php`
- `backend/modules/judiciaryType/views/judiciary-type/_search.php`
- `backend/modules/judiciaryType/views/judiciary-type/create.php`
- `backend/modules/judiciaryType/views/judiciary-type/index.php`
- `backend/modules/judiciaryType/views/judiciary-type/update.php`
- `backend/modules/judiciaryType/views/judiciary-type/view.php`
- `backend/modules/lawyers/views/lawyers/_columns.php`
- `backend/modules/lawyers/views/lawyers/_form.php`
- `backend/modules/lawyers/views/lawyers/_search.php`
- `backend/modules/lawyers/views/lawyers/create.php`
- `backend/modules/lawyers/views/lawyers/index.php`
- `backend/modules/lawyers/views/lawyers/update.php`
- `backend/modules/lawyers/views/lawyers/view.php`
- `backend/modules/LawyersImage/views/default/index.php`
- `backend/modules/leavePolicy/views/leave-policy/_columns.php`
- `backend/modules/leavePolicy/views/leave-policy/_form.php`
- `backend/modules/leavePolicy/views/leave-policy/create.php`
- `backend/modules/leavePolicy/views/leave-policy/index.php`
- `backend/modules/leavePolicy/views/leave-policy/update.php`
- `backend/modules/leavePolicy/views/leave-policy/view.php`
- `backend/modules/leaveRequest/views/leave-request/_columns.php`
- `backend/modules/leaveRequest/views/leave-request/_form.php`
- `backend/modules/leaveRequest/views/leave-request/create.php`
- `backend/modules/leaveRequest/views/leave-request/index.php`
- `backend/modules/leaveRequest/views/leave-request/suspended_vacations.php`
- `backend/modules/leaveRequest/views/leave-request/update.php`
- `backend/modules/leaveRequest/views/leave-request/view.php`
- `backend/modules/leaveTypes/views/leave-types/_columns.php`
- `backend/modules/leaveTypes/views/leave-types/_form.php`
- `backend/modules/leaveTypes/views/leave-types/create.php`
- `backend/modules/leaveTypes/views/leave-types/index.php`
- `backend/modules/leaveTypes/views/leave-types/update.php`
- `backend/modules/leaveTypes/views/leave-types/view.php`
- `backend/modules/loanScheduling/views/loan-scheduling/_columns.php`
- `backend/modules/loanScheduling/views/loan-scheduling/_form-follow-up.php`
- `backend/modules/loanScheduling/views/loan-scheduling/_form.php`
- `backend/modules/loanScheduling/views/loan-scheduling/_search.php`
- `backend/modules/loanScheduling/views/loan-scheduling/create.php`
- `backend/modules/loanScheduling/views/loan-scheduling/index.php`
- `backend/modules/loanScheduling/views/loan-scheduling/update.php`
- `backend/modules/loanScheduling/views/loan-scheduling/view.php`
- `backend/modules/location/views/location/_columns.php`
- `backend/modules/location/views/location/_form.php`
- `backend/modules/location/views/location/_search.php`
- `backend/modules/location/views/location/create.php`
- `backend/modules/location/views/location/index.php`
- `backend/modules/location/views/location/update.php`
- `backend/modules/location/views/location/view.php`
- `backend/modules/movment/views/movment/_columns.php`
- `backend/modules/movment/views/movment/_form.php`
- `backend/modules/movment/views/movment/_search.php`
- `backend/modules/movment/views/movment/create.php`
- `backend/modules/movment/views/movment/index.php`
- `backend/modules/movment/views/movment/update.php`
- `backend/modules/movment/views/movment/view.php`
- `backend/modules/notification/views/notification/_all-user-msg.php`
- `backend/modules/notification/views/notification/_columns.php`
- `backend/modules/notification/views/notification/_form.php`
- `backend/modules/notification/views/notification/_search.php`
- `backend/modules/notification/views/notification/_user-columns.php`
- `backend/modules/notification/views/notification/create.php`
- `backend/modules/notification/views/notification/index.php`
- `backend/modules/notification/views/notification/update.php`
- `backend/modules/notification/views/notification/view.php`
- `backend/modules/officialHolidays/views/official-holidays/_columns.php`
- `backend/modules/officialHolidays/views/official-holidays/_form.php`
- `backend/modules/officialHolidays/views/official-holidays/create.php`
- `backend/modules/officialHolidays/views/official-holidays/index.php`
- `backend/modules/officialHolidays/views/official-holidays/update.php`
- `backend/modules/officialHolidays/views/official-holidays/view.php`
- `backend/modules/paymentType/views/payment-type/_columns.php`
- `backend/modules/paymentType/views/payment-type/_form.php`
- `backend/modules/paymentType/views/payment-type/create.php`
- `backend/modules/paymentType/views/payment-type/index.php`
- `backend/modules/paymentType/views/payment-type/update.php`
- `backend/modules/paymentType/views/payment-type/view.php`
- `backend/modules/phoneNumbers/views/phone-numbers/_columns.php`
- `backend/modules/phoneNumbers/views/phone-numbers/_form.php`
- `backend/modules/phoneNumbers/views/phone-numbers/create.php`
- `backend/modules/phoneNumbers/views/phone-numbers/index.php`
- `backend/modules/phoneNumbers/views/phone-numbers/update.php`
- `backend/modules/phoneNumbers/views/phone-numbers/view.php`
- `backend/modules/profitDistribution/views/profit-distribution/create-portfolio.php`
- `backend/modules/profitDistribution/views/profit-distribution/create-shareholders.php`
- `backend/modules/profitDistribution/views/profit-distribution/index.php`
- `backend/modules/profitDistribution/views/profit-distribution/view.php`
- `backend/modules/realEstate/views/real-estate/_columns.php`
- `backend/modules/realEstate/views/real-estate/_form.php`
- `backend/modules/realEstate/views/real-estate/create.php`
- `backend/modules/realEstate/views/real-estate/index.php`
- `backend/modules/realEstate/views/real-estate/update.php`
- `backend/modules/realEstate/views/real-estate/view.php`
- `backend/modules/rejesterFollowUpType/views/rejester-follow-up-type/_columns.php`
- `backend/modules/rejesterFollowUpType/views/rejester-follow-up-type/_form.php`
- `backend/modules/rejesterFollowUpType/views/rejester-follow-up-type/_search.php`
- `backend/modules/rejesterFollowUpType/views/rejester-follow-up-type/create.php`
- `backend/modules/rejesterFollowUpType/views/rejester-follow-up-type/index.php`
- `backend/modules/rejesterFollowUpType/views/rejester-follow-up-type/update.php`
- `backend/modules/rejesterFollowUpType/views/rejester-follow-up-type/view.php`
- `backend/modules/reports/views/customers-judiciary-actions-report/_columns.php`
- `backend/modules/reports/views/customers-judiciary-actions-report/_search.php`
- `backend/modules/reports/views/customers-judiciary-actions-report/index.php`
- `backend/modules/reports/views/customers-judiciary-actions-report/view.php`
- `backend/modules/reports/views/default/index.php`
- `backend/modules/reports/views/due_installment.php`
- `backend/modules/reports/views/follow-up-reports/_columns.php`
- `backend/modules/reports/views/follow-up-reports/_search.php`
- `backend/modules/reports/views/follow-up-reports/index.php`
- `backend/modules/reports/views/follow-up-reports/view.php`
- `backend/modules/reports/views/income-reports/_columns.php`
- `backend/modules/reports/views/income-reports/_custamer-judiciary-search.php`
- `backend/modules/reports/views/income-reports/_custamer-search.php`
- `backend/modules/reports/views/income-reports/index.php`
- `backend/modules/reports/views/income-reports/TotalCustomerPaymentsIndex.php`
- `backend/modules/reports/views/income-reports/TotalJudiciaryCustomerPaymentsIndex.php`
- `backend/modules/reports/views/income-reports/view.php`
- `backend/modules/reports/views/index.php`
- `backend/modules/reports/views/judiciary/_columns.php`
- `backend/modules/reports/views/judiciary/_form.php`
- `backend/modules/reports/views/judiciary/_report_columns.php`
- `backend/modules/reports/views/judiciary/_search.php`
- `backend/modules/reports/views/judiciary/create.php`
- `backend/modules/reports/views/judiciary/index.php`
- `backend/modules/reports/views/judiciary/report.php`
- `backend/modules/reports/views/judiciary/update.php`
- `backend/modules/reports/views/judiciary/view.php`
- `backend/modules/reports/views/monthly_installment.php`
- `backend/modules/reports/views/monthly_installment_monthly_beer_user.php`
- `backend/modules/reports/views/reports/index.php`
- `backend/modules/reports/views/this_month_installments.php`
- `backend/modules/sharedExpenses/views/shared-expense/_form.php`
- `backend/modules/sharedExpenses/views/shared-expense/create.php`
- `backend/modules/sharedExpenses/views/shared-expense/index.php`
- `backend/modules/sharedExpenses/views/shared-expense/update.php`
- `backend/modules/sharedExpenses/views/shared-expense/view.php`
- `backend/modules/shareholders/views/shareholders/_form.php`
- `backend/modules/shareholders/views/shareholders/_search.php`
- `backend/modules/shareholders/views/shareholders/create.php`
- `backend/modules/shareholders/views/shareholders/index.php`
- `backend/modules/shareholders/views/shareholders/update.php`
- `backend/modules/shareholders/views/shareholders/view.php`
- `backend/modules/shares/views/shares/_form.php`
- `backend/modules/shares/views/shares/_search.php`
- `backend/modules/shares/views/shares/create.php`
- `backend/modules/shares/views/shares/index.php`
- `backend/modules/shares/views/shares/update.php`
- `backend/modules/shares/views/shares/view.php`
- `backend/modules/sms/views/sms/_columns.php`
- `backend/modules/sms/views/sms/_form.php`
- `backend/modules/sms/views/sms/_pop_up.php`
- `backend/modules/sms/views/sms/_search.php`
- `backend/modules/sms/views/sms/create.php`
- `backend/modules/sms/views/sms/index.php`
- `backend/modules/sms/views/sms/update.php`
- `backend/modules/sms/views/sms/view.php`
- `backend/modules/status/views/status/_columns.php`
- `backend/modules/status/views/status/_form.php`
- `backend/modules/status/views/status/create.php`
- `backend/modules/status/views/status/index.php`
- `backend/modules/status/views/status/update.php`
- `backend/modules/status/views/status/view.php`
- `backend/modules/workdays/views/workdays/_columns.php`
- `backend/modules/workdays/views/workdays/_form.php`
- `backend/modules/workdays/views/workdays/create.php`
- `backend/modules/workdays/views/workdays/index.php`
- `backend/modules/workdays/views/workdays/update.php`
- `backend/modules/workdays/views/workdays/view.php`
- `backend/views/_section_tabs.php`
- `backend/views/layouts/_diwan-tabs.php`
- `backend/views/layouts/_financial-tabs.php`
- `backend/views/layouts/_inventory-tabs.php`
- `backend/views/layouts/_menu_items.php`
- `backend/views/layouts/_reports-tabs.php`
- `backend/views/layouts/absolute.php`
- `backend/views/layouts/content.php`
- `backend/views/layouts/footer.php`
- `backend/views/layouts/header.php`
- `backend/views/layouts/left.php`
- `backend/views/layouts/login_layout/content.php`
- `backend/views/layouts/login_layout/main.php`
- `backend/views/layouts/main-login.php`
- `backend/views/layouts/main-v3.php`
- `backend/views/layouts/main.php`
- `backend/views/layouts/modal-ajax.php`
- `backend/views/layouts/navigation.php`
- `backend/views/layouts/overall.php`
- `backend/views/layouts/print-template-1.php`
- `backend/views/layouts/print_cases.php`
- `backend/views/layouts/print_templete_2.php`
- `backend/views/layouts/printe_content.php`
- `backend/views/layouts/printer_content.php`
- `backend/views/layouts/profile_layout/content_profile.php`
- `backend/views/layouts/profile_layout/main_profile.php`
- `backend/views/permissions-management/index.php`
- `backend/views/site/error.php`
- `backend/views/site/image-manager.php`
- `backend/views/site/index.php`
- `backend/views/site/system-settings.php`
- `backend/views/user-tools/index.php`
- `backend/views/user/_alert.php`
- `backend/views/user/security/login.php`
- `backend/views/v.php`

## افتراضات التقرير

- **نطاق المسح:** `backend/views/**/*.php` و `backend/modules/**/views/**/*.php` فقط.
- **الشاشات:** استبعاد أي مسار يحتوي مجلد `layouts` أو `mail`، واستبعاد الملفات التي يبدأ اسمها بـ `_`.
- **الاسم العربي:** الأفضلية لـ `$this->title` ثم أول `<h1>`؛ إن وُجد نص عربي يُعتمد دون وسم تقديري. وإلا يُذكر النص غير العربي مع `(تقديري من الكود)` أو وصف من المسار.
- **الوظيفة:** جملة مختصرة استنتاجية من اسم الملف/المجلد (مثل index/create/update/view).
- **الفورمز:** كل ملف اسمه ينتهي بـ `_form.php` وأي ملف يحتوي `ActiveForm::begin`؛ نوع الفورم استنتاجي من الاسم والمسار والسياق (Modal/بحث/رئيسي/خاص).
<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Arabic language pack for Cohort Manager
 *
 * @package    local_cohortmanager
 * @category   string
 * @copyright  2025 Davison Almeida <ramosdealmeidasistemas@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'مدير المجموعات';

// App component strings
$string['cohortmanager'] = 'مدير المجموعات';
$string['newcohort'] = 'جديد';
$string['addcohort'] = 'إضافة مجموعة';
$string['searchcohorts'] = 'بحث...';
$string['loadingcohorts'] = 'جارٍ التحميل...';
$string['name'] = 'الاسم';
$string['idnumber'] = 'رقم المعرف';
$string['description'] = 'الوصف';
$string['visible'] = 'مرئي';
$string['hidden'] = 'مخفي';
$string['actions'] = 'الإجراءات';
$string['edit'] = 'تحرير';
$string['delete'] = 'حذف';
$string['id'] = 'المعرف';
$string['page'] = 'صفحة';
$string['of'] = 'من';
$string['nocoortsfound'] = 'لم يتم العثور';
$string['nocoortsfounddesc'] = 'لم يتم العثور على نتائج.';
$string['createyourfirstcohort'] = 'أنشئ مجموعتك الأولى للبدء.';
$string['createnewcohort'] = 'إضافة جديد';
$string['cancel'] = 'إلغاء';
$string['save'] = 'حفظ';
$string['nodecription'] = 'لا يوجد وصف';
$string['nodescription'] = 'لا يوجد وصف';
$string['failedtoloadcohorts'] = 'فشل التحميل. حاول مرة أخرى.';
$string['failedtodeletecohort'] = 'فشل الحذف. حاول مرة أخرى.';
$string['failedtosavecohort'] = 'فشل الحفظ. حاول مرة أخرى.';
$string['deleteconfirmation'] = 'هل أنت متأكد من حذف "%s"؟';
$string['deletethiscohort'] = 'حذف';
$string['cohortdeletedsuccessfully'] = 'تم الحذف بنجاح';
$string['instanceexists'] = 'يوجد بالفعل مثيل تسجيل بهذه المجموعة والدور في المقرر الدراسي. جرب دوراً آخر.';

// CohortCreate component strings
$string['basicinformation'] = 'المعلومات الأساسية';
$string['cohortnamedescription'] = 'الاسم المعروض';
$string['makecohortvisible'] = 'اجعله مرئياً للمستخدمين';
$string['theme'] = 'السمة';
$string['settings'] = 'الإعدادات';
$string['category'] = 'التصنيف';
$string['systemcategory'] = 'النظام';
$string['coursecategorybyidnumber'] = 'برقم المعرف';
$string['coursecategorybyid'] = 'بالمعرف';
$string['entercoursecategoryidnumber'] = 'أدخل رقم معرف التصنيف';
$string['entercoursecategoryid'] = 'أدخل معرف التصنيف';
$string['reset'] = 'إعادة تعيين';
$string['creating'] = 'جارٍ الإضافة...';
$string['createcohort'] = 'إضافة';
$string['failedtocreatecohort'] = 'فشلت الإضافة. حاول مرة أخرى.';
$string['resetform'] = 'إعادة تعيين النموذج';
$string['pleaseentercohortname'] = 'يرجى إدخال اسم';
$string['pleaseenteridnumber'] = 'يرجى إدخال رقم معرف';
$string['cohortcreatedsuccessfully'] = 'تمت الإضافة بنجاح';
$string['cohortname'] = 'الاسم';
$string['entercohortname'] = 'أدخل الاسم';
$string['enteridnumber'] = 'أدخل رقم المعرف';
$string['idnumberdescription'] = 'المعرف الفريد';
$string['entercohortdescription'] = 'أدخل الوصف';
$string['cohortdescription'] = 'وصف مختصر';

// CohortDetail component strings
$string['loadingcohortdetails'] = 'جارٍ تحميل التفاصيل...';
$string['cohortnotfound'] = 'غير موجود';
$string['failedtoloadcohortdetails'] = 'فشل تحميل التفاصيل. حاول مرة أخرى.';
$string['backtolist'] = 'العودة إلى القائمة';
$string['retry'] = 'إعادة المحاولة';
$string['cohortnotfounddetails'] = 'قد يكون العنصر الذي تبحث عنه قد تم حذفه أو غير موجود.';
$string['details'] = 'التفاصيل';
$string['cohortportrait'] = 'نظرة عامة على المجموعة';
$string['members'] = 'الأعضاء';
$string['memberscount'] = 'عضو';
$string['enrolinstances'] = 'مثيلات التسجيل';
$string['totalenrolments'] = 'إجمالي المسجلين';
$string['roleassignments'] = 'تعيينات الأدوار';
$string['visibility'] = 'الرؤية';
$string['nodescriptionprovided'] = 'لم يتم توفير وصف.';
$string['customfields'] = 'الحقول المخصصة';
$string['cohortmembers'] = 'الأعضاء';
$string['nomembersfound'] = 'لم يتم العثور على أعضاء';
$string['nomembersfounddescription'] = 'لم يتم العثور على أعضاء في هذه المجموعة. أضف أعضاء للبدء.';
$string['backtocohortlist'] = 'العودة إلى القائمة';
$string['username'] = 'اسم المستخدم';
$string['email'] = 'البريد الإلكتروني';

// CohortEdit component strings
$string['loadingcohortdata'] = 'جارٍ تحميل البيانات...';
$string['failedtoloadcohortdata'] = 'فشل تحميل البيانات. حاول مرة أخرى.';
$string['editcohort'] = 'تحرير';
$string['failedtoupdatecohort'] = 'فشل التحديث. حاول مرة أخرى.';
$string['saving'] = 'جارٍ الحفظ...';
$string['savechanges'] = 'حفظ التغييرات';
$string['cohortupdatedsuccessfully'] = 'تم التحديث بنجاح';

// ThemeSelect component strings
$string['themedescription'] = 'اختر سمة';

// ContextSelect component strings
$string['context'] = 'السياق';
$string['selectcontext'] = 'اختر السياق';
$string['searchcontext'] = 'البحث في السياقات...';
$string['nocontextsfound'] = 'لم يتم العثور على سياقات';
$string['entercoursecontextid'] = 'أدخل معرف سياق المقرر الدراسي';
$string['systemcontext'] = 'النظام';
$string['loading'] = 'جارٍ التحميل...';
$string['search'] = 'بحث';

// CohortDelete component strings
$string['deletecohort'] = 'حذف';

// CohortCustomFieldsManagement component strings
$string['customfieldsmanagement'] = 'مدير الحقول المخصصة';
$string['back'] = 'رجوع';
$string['backtohome'] = 'العودة إلى الصفحة الرئيسية';

// Custom field form strings
$string['customfieldssaved'] = 'تم حفظ الحقول المخصصة بنجاح.';
$string['errorprocessingcustomfields'] = 'خطأ في معالجة الحقول المخصصة. حاول مرة أخرى.';
$string['customfieldnotvalid'] = 'حقول مخصصة غير صالحة.';

// CohortMembersAddModal component strings
$string['addmembers'] = 'إضافة أعضاء';
$string['add'] = 'إضافة';
$string['membersadded'] = 'تمت إضافة الأعضاء بنجاح';

// Delete membership confirmation strings
$string['delete_membership_confirmation'] = 'هل أنت متأكد من إزالة الأعضاء {$a} المحددين؟';
$string['delete_membership_warning'] = 'تحذير: هذا الإجراء دائم ولا يمكن التراجع عنه.';
$string['selected_members_list'] = 'المحدد: {$a}';

// CohortEnrolInstancesPartial component strings
$string['addenrolinstance'] = 'إضافة مثيل تسجيل';
$string['cohortenrolinstances'] = 'مثيلات التسجيل';
$string['instancescount'] = 'مثيل';
$string['loadingenrolinstances'] = 'جارٍ تحميل مثيلات التسجيل...';
$string['noenrolinstancesfound'] = 'لم يتم العثور على مثيلات تسجيل';
$string['noenrolinstancesfounddescription'] = 'لم يتم العثور على مثيلات تسجيل.';
$string['enrolinstanceslist'] = 'مثيلات التسجيل';
$string['refresh'] = 'تحديث';
$string['course'] = 'المقرر الدراسي';
$string['enrolmethod'] = 'طريقة التسجيل';
$string['status'] = 'الحالة';
$string['active'] = 'نشط';
$string['inactive'] = 'غير نشط';
$string['cohortenrol'] = 'مجموعة';
$string['view'] = 'عرض';
$string['deleteenrolinstance'] = 'حذف مثيل التسجيل';
$string['deleteenrolinstanceconfirmation'] = 'هل أنت متأكد من حذف مثيل التسجيل لـ "%s"؟';
$string['deleteenrolinstancewarning'] = 'تحذير: سيؤدي هذا إلى إزالة {$a} من تسجيلات المستخدمين من هذا المقرر الدراسي.';
$string['enrolinstancedeletedsuccessfully'] = 'تم حذف مثيل التسجيل بنجاح';
$string['searchenrolinstances'] = 'البحث في مثيلات التسجيل...';
$string['filterbystatus'] = 'تصفية حسب الحالة';
$string['all'] = 'الكل';
$string['togglestatus'] = 'تبديل الحالة';
$string['enrolinstancestatusupdated'] = 'تم تحديث حالة مثيل التسجيل بنجاح';
$string['errortogglingstatus'] = 'خطأ في تحديث حالة مثيل التسجيل';
$string['enrolinstanceupdated'] = 'تم تحديث مثيل التسجيل بنجاح';

// Roles management component strings
$string['rolesmanagement'] = 'إدارة الأدوار';
$string['usercontextroles'] = 'الأدوار في سياق المستخدم';
$string['searchroles'] = 'البحث في الأدوار...';
$string['loadingroles'] = 'جارٍ تحميل الأدوار...';
$string['rolename'] = 'اسم الدور';
$string['roleshortname'] = 'الاسم المختصر';
$string['roleid'] = 'المعرف';
$string['rolesortorder'] = 'الترتيب';
$string['rolearchetype'] = 'النمط الأصلي';
$string['roleactions'] = 'الإجراءات';
$string['editrole'] = 'تحرير';
$string['deleterole'] = 'حذف';
$string['createnewrole'] = 'إنشاء دور جديد';
$string['addnewrole'] = 'إضافة دور جديد';
$string['rolecreatedsuccessfully'] = 'تم إنشاء الدور بنجاح';
$string['roleupdatedsuccessfully'] = 'تم تحديث الدور بنجاح';
$string['roledeletedsuccessfully'] = 'تم حذف الدور بنجاح';
$string['rolenotfound'] = 'الدور غير موجود';
$string['roleexists'] = 'يوجد دور بهذا الاسم المختصر بالفعل';
$string['noassignableroles'] = 'لا توجد أدوار قابلة للتعيين في سياق المستخدم.';
$string['roledescriptionplaceholder'] = 'أدخل الوصف (اختياري)';
$string['rolearchetypeplaceholder'] = 'اختر النمط الأصلي (اختياري)';
$string['rolecreate'] = 'إنشاء دور';
$string['roleupdate'] = 'تحديث دور';
$string['roledeleteconfirmation'] = 'هل أنت متأكد من حذف "%s"؟ لا يمكن التراجع عن هذا الإجراء.';
$string['deletethisrole'] = 'حذف هذا الدور';
$string['cannotdeletethisrole'] = 'لا يمكن حذف هذا الدور لأنه دور نظام.';
$string['cannotdeleterolewithid'] = 'لا يمكن حذف الدور ذو المعرف {$a}.';
$string['rolesearchplaceholder'] = 'البحث بالاسم أو الاسم المختصر أو الاسم المخصص...';
$string['rolescount'] = 'دور';
$string['norolesfound'] = 'لم يتم العثور على أدوار';
$string['norolesfounddesc'] = 'لم يتم العثور على أدوار.';
$string['createyourfirstrole'] = 'أنشئ دورك الأول للبدء.';

// DynamicCohortMembersTable component strings
$string['searchmembers'] = 'البحث في الأعضاء...';
$string['first'] = 'الأول';
$string['last'] = 'الأخير';
$string['columns'] = 'الأعمدة';
$string['city'] = 'المدينة';
$string['country'] = 'الدولة';
$string['showing'] = 'عرض';
$string['to'] = 'إلى';
$string['previous'] = 'السابق';
$string['next'] = 'التالي';
$string['failedtofetchmembers'] = 'فشل في جلب الأعضاء';
$string['anerroroccurred'] = 'حدث خطأ';
$string['unknown'] = 'غير معروف';
$string['errordeletingmembers'] = 'حدث خطأ أثناء حذف الأعضاء';

// CohortEnrolInstancesSearchModal component strings
$string['searchcourses'] = 'البحث في المقررات الدراسية';
$string['searchcoursesplaceholder'] = 'البحث في المقررات الدراسية...';
$string['searching'] = 'جارٍ البحث...';
$string['nocoursesfound'] = 'لم يتم العثور على مقررات دراسية';
$string['selectedcourses'] = 'المقررات الدراسية المحددة';
$string['coursealreadyselected'] = 'المقرر الدراسي محدد بالفعل';
$string['noselectedcourses'] = 'لم يتم تحديد مقررات دراسية';
$string['role'] = 'الدور';
$string['group'] = 'مجموعة';
$string['remove'] = 'إزالة';
$string['enroled'] = 'مسجل';
$string['groupmembers'] = 'أعضاء المجموعة';
$string['enrolinstanceadded'] = 'تمت إضافة مثيل التسجيل بنجاح';
$string['errorcreatingenrolinstances'] = 'خطأ في إنشاء مثيلات التسجيل. حاول مرة أخرى.';

// Cohort role assignments (tool_cohortroles integration) strings
$string['cohortroles'] = 'أدوار المجموعة';
$string['cohortroleassignments'] = 'التعيينات';
$string['cohortroleassignmentscount'] = 'تعيين';
$string['loadingcohortroles'] = 'جارٍ تحميل تعيينات الأدوار...';
$string['noroughassignmentsfound'] = 'لم يتم العثور على تعيينات أدوار';
$string['noroughassignmentsfounddescription'] = 'لم يتم العثور على تعيينات. قم بتعيين المستخدمين بأدوار لإنشاء تسلسل هرمي.';
$string['createyourfirstassignment'] = 'أنشئ تعيينك الأول للبدء.';
$string['addroleassignment'] = 'إضافة تعيين';
$string['assigneduser'] = 'المستخدم';
$string['assignedrole'] = 'الدور';
$string['assignedcohort'] = 'المجموعة';
$string['assignedtime'] = 'تاريخ التعيين';
$string['deleteassignment'] = 'حذف التعيين';
$string['deleteassignmentconfirmation'] = 'هل أنت متأكد من حذف هذا التعيين؟ سيتم إزالته أثناء المزامنة التالية.';
$string['cohortroleassignmentcreated'] = 'تم إنشاء التعيين. سيصبح سارياً بعد المزامنة المجدولة التالية.';
$string['cohortroleassignmentdeleted'] = 'تم حذف التعيين بنجاح.';
$string['cohortroleassignmentexists'] = 'هذا التعيين موجود بالفعل.';
$string['backgroundsyncnote'] = 'تصبح التعيينات سارية بعد تشغيل مهمة المزامنة المجدولة.';
$string['synclastsync'] = 'آخر';
$string['syncnextsync'] = 'التالي';
$string['syncnever'] = 'أبداً';
$string['selectuser'] = 'اختر مستخدماً';
$string['searchusers'] = 'البحث عن مستخدمين...';
$string['selectrole'] = 'اختر دوراً';
$string['nousersfound'] = 'لم يتم العثور على مستخدمين';
$string['assignroletouser'] = 'تعيين دور';
$string['userrolecohortinfo'] = 'تعيين دور في سياق المستخدم لجميع الأعضاء.';
$string['assignedon'] = 'تم التعيين في';
$string['deletethisassignment'] = 'حذف هذا التعيين';
$string['assigneduserscount'] = 'التعيينات';
$string['perpage'] = 'في الصفحة';
$string['formvalidationerror'] = 'يرجى تصحيح الأخطاء في النموذج.';

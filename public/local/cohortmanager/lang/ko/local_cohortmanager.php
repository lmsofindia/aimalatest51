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
 * Korean language pack for Cohort Manager
 *
 * @package    local_cohortmanager
 * @category   string
 * @copyright  2025 Davison Almeida <ramosdealmeidasistemas@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = '코호트 관리자';

// App component strings
$string['cohortmanager'] = '코호트 관리자';
$string['newcohort'] = '새로 만들기';
$string['addcohort'] = '코호트 추가';
$string['searchcohorts'] = '검색...';
$string['loadingcohorts'] = '로딩 중...';
$string['name'] = '이름';
$string['idnumber'] = 'ID 번호';
$string['description'] = '설명';
$string['visible'] = '표시';
$string['hidden'] = '숨김';
$string['actions'] = '작업';
$string['edit'] = '편집';
$string['delete'] = '삭제';
$string['id'] = 'ID';
$string['page'] = '페이지';
$string['of'] = '/';
$string['nocoortsfound'] = '검색 결과 없음';
$string['nocoortsfounddesc'] = '검색 조건과 일치하는 결과가 없습니다.';
$string['createyourfirstcohort'] = '시작하려면 첫 번째 코호트를 만드세요.';
$string['createnewcohort'] = '새로 추가';
$string['cancel'] = '취소';
$string['save'] = '저장';
$string['nodecription'] = '설명 없음';
$string['nodescription'] = '설명 없음';
$string['failedtoloadcohorts'] = '로드 실패. 다시 시도해 주세요.';
$string['failedtodeletecohort'] = '삭제 실패. 다시 시도해 주세요.';
$string['failedtosavecohort'] = '저장 실패. 다시 시도해 주세요.';
$string['deleteconfirmation'] = '"%s"을(를) 삭제하시겠습니까?';
$string['deletethiscohort'] = '삭제';
$string['cohortdeletedsuccessfully'] = '성공적으로 삭제되었습니다';
$string['instanceexists'] = '이 코스에 이미 이 코호트와 역할에 대한 등록 인스턴스가 있습니다. 다른 역할을 시도해 보세요.';

// CohortCreate component strings
$string['basicinformation'] = '기본 정보';
$string['cohortnamedescription'] = '표시 이름';
$string['makecohortvisible'] = '사용자에게 표시';
$string['theme'] = '테마';
$string['settings'] = '설정';
$string['category'] = '카테고리';
$string['systemcategory'] = '시스템';
$string['coursecategorybyidnumber'] = 'ID 번호로';
$string['coursecategorybyid'] = 'ID로';
$string['entercoursecategoryidnumber'] = '카테고리 ID 번호 입력';
$string['entercoursecategoryid'] = '카테고리 ID 입력';
$string['reset'] = '초기화';
$string['creating'] = '추가 중...';
$string['createcohort'] = '추가';
$string['failedtocreatecohort'] = '추가 실패. 다시 시도해 주세요.';
$string['resetform'] = '양식 초기화';
$string['pleaseentercohortname'] = '이름을 입력해 주세요';
$string['pleaseenteridnumber'] = 'ID 번호를 입력해 주세요';
$string['cohortcreatedsuccessfully'] = '성공적으로 추가되었습니다';
$string['cohortname'] = '이름';
$string['entercohortname'] = '이름 입력';
$string['enteridnumber'] = 'ID 번호 입력';
$string['idnumberdescription'] = '고유 식별자';
$string['entercohortdescription'] = '설명 입력';
$string['cohortdescription'] = '간단한 설명';

// CohortDetail component strings
$string['loadingcohortdetails'] = '세부 정보 로딩 중...';
$string['cohortnotfound'] = '찾을 수 없음';
$string['failedtoloadcohortdetails'] = '세부 정보 로드 실패. 다시 시도해 주세요.';
$string['backtolist'] = '목록으로 돌아가기';
$string['retry'] = '재시도';
$string['cohortnotfounddetails'] = '찾고 있는 항목이 삭제되었거나 존재하지 않을 수 있습니다.';
$string['details'] = '세부 정보';
$string['cohortportrait'] = '코호트 개요';
$string['members'] = '회원';
$string['memberscount'] = '명';
$string['enrolinstances'] = '등록 인스턴스';
$string['totalenrolments'] = '총 등록자';
$string['roleassignments'] = '역할 할당';
$string['visibility'] = '표시 여부';
$string['nodescriptionprovided'] = '제공된 설명이 없습니다.';
$string['customfields'] = '사용자 정의 필드';
$string['cohortmembers'] = '회원';
$string['nomembersfound'] = '회원을 찾을 수 없음';
$string['nomembersfounddescription'] = '이 코호트에서 회원을 찾을 수 없습니다. 회원을 추가하여 시작하세요.';
$string['backtocohortlist'] = '목록으로 돌아가기';
$string['username'] = '사용자 이름';
$string['email'] = '이메일';

// CohortEdit component strings
$string['loadingcohortdata'] = '데이터 로딩 중...';
$string['failedtoloadcohortdata'] = '데이터 로드 실패. 다시 시도해 주세요.';
$string['editcohort'] = '편집';
$string['failedtoupdatecohort'] = '업데이트 실패. 다시 시도해 주세요.';
$string['saving'] = '저장 중...';
$string['savechanges'] = '변경사항 저장';
$string['cohortupdatedsuccessfully'] = '성공적으로 업데이트되었습니다';

// ThemeSelect component strings
$string['themedescription'] = '테마 선택';

// ContextSelect component strings
$string['context'] = '컨텍스트';
$string['selectcontext'] = '컨텍스트 선택';
$string['searchcontext'] = '컨텍스트 검색...';
$string['nocontextsfound'] = '컨텍스트를 찾을 수 없음';
$string['entercoursecontextid'] = '코스 컨텍스트 ID 입력';
$string['systemcontext'] = '시스템';
$string['loading'] = '로딩 중...';
$string['search'] = '검색';

// CohortDelete component strings
$string['deletecohort'] = '삭제';

// CohortCustomFieldsManagement component strings
$string['customfieldsmanagement'] = '사용자 정의 필드 관리자';
$string['back'] = '뒤로';
$string['backtohome'] = '홈으로 돌아가기';

// Custom field form strings
$string['customfieldssaved'] = '사용자 정의 필드가 저장되었습니다.';
$string['errorprocessingcustomfields'] = '사용자 정의 필드 처리 중 오류가 발생했습니다. 다시 시도해 주세요.';
$string['customfieldnotvalid'] = '잘못된 사용자 정의 필드입니다.';

// CohortMembersAddModal component strings
$string['addmembers'] = '회원 추가';
$string['add'] = '추가';
$string['membersadded'] = '회원이 추가되었습니다';

// Delete membership confirmation strings
$string['delete_membership_confirmation'] = '선택한 {$a}명의 회원을 제거하시겠습니까?';
$string['delete_membership_warning'] = '경고: 이 작업은 영구적이며 취소할 수 없습니다.';
$string['selected_members_list'] = '선택됨: {$a}';

// CohortEnrolInstancesPartial component strings
$string['addenrolinstance'] = '등록 인스턴스 추가';
$string['cohortenrolinstances'] = '등록 인스턴스';
$string['instancescount'] = '개 인스턴스';
$string['loadingenrolinstances'] = '등록 인스턴스 로딩 중...';
$string['noenrolinstancesfound'] = '등록 인스턴스를 찾을 수 없음';
$string['noenrolinstancesfounddescription'] = '등록 인스턴스를 찾을 수 없습니다.';
$string['enrolinstanceslist'] = '등록 인스턴스';
$string['refresh'] = '새로고침';
$string['course'] = '코스';
$string['enrolmethod'] = '방법';
$string['status'] = '상태';
$string['active'] = '활성';
$string['inactive'] = '비활성';
$string['cohortenrol'] = '코호트';
$string['view'] = '보기';
$string['deleteenrolinstance'] = '등록 인스턴스 삭제';
$string['deleteenrolinstanceconfirmation'] = '"%s"에 대한 등록 인스턴스를 삭제하시겠습니까?';
$string['deleteenrolinstancewarning'] = '경고: 이 코스에서 {$a}개의 사용자 등록이 제거됩니다.';
$string['enrolinstancedeletedsuccessfully'] = '등록 인스턴스가 삭제되었습니다';
$string['searchenrolinstances'] = '등록 인스턴스 검색...';
$string['filterbystatus'] = '상태별 필터';
$string['all'] = '모두';
$string['togglestatus'] = '상태 전환';
$string['enrolinstancestatusupdated'] = '등록 인스턴스 상태가 업데이트되었습니다';
$string['errortogglingstatus'] = '등록 인스턴스 상태 업데이트 중 오류 발생';
$string['enrolinstanceupdated'] = '등록 인스턴스가 업데이트되었습니다';

// Roles management component strings
$string['rolesmanagement'] = '역할 관리';
$string['usercontextroles'] = '사용자 컨텍스트 역할';
$string['searchroles'] = '역할 검색...';
$string['loadingroles'] = '역할 로딩 중...';
$string['rolename'] = '역할 이름';
$string['roleshortname'] = '짧은 이름';
$string['roleid'] = 'ID';
$string['rolesortorder'] = '순서';
$string['rolearchetype'] = '원형';
$string['roleactions'] = '작업';
$string['editrole'] = '편집';
$string['deleterole'] = '삭제';
$string['createnewrole'] = '새 역할 만들기';
$string['addnewrole'] = '새 역할 추가';
$string['rolecreatedsuccessfully'] = '역할이 생성되었습니다';
$string['roleupdatedsuccessfully'] = '역할이 업데이트되었습니다';
$string['roledeletedsuccessfully'] = '역할이 삭제되었습니다';
$string['rolenotfound'] = '역할을 찾을 수 없음';
$string['roleexists'] = '이 짧은 이름을 가진 역할이 이미 존재합니다';
$string['noassignableroles'] = '사용자 컨텍스트에서 할당 가능한 역할이 없습니다.';
$string['roledescriptionplaceholder'] = '설명 입력 (선택 사항)';
$string['rolearchetypeplaceholder'] = '원형 선택 (선택 사항)';
$string['rolecreate'] = '역할 만들기';
$string['roleupdate'] = '역할 업데이트';
$string['roledeleteconfirmation'] = '"%s"을(를) 삭제하시겠습니까? 이 작업은 취소할 수 없습니다.';
$string['deletethisrole'] = '이 역할 삭제';
$string['cannotdeletethisrole'] = '시스템 역할이므로 이 역할을 삭제할 수 없습니다.';
$string['cannotdeleterolewithid'] = 'ID {$a}인 역할을 삭제할 수 없습니다.';
$string['rolesearchplaceholder'] = '이름, 짧은 이름 또는 사용자 정의 이름으로 검색...';
$string['rolescount'] = '개 역할';
$string['norolesfound'] = '역할을 찾을 수 없음';
$string['norolesfounddesc'] = '역할을 찾을 수 없습니다.';
$string['createyourfirstrole'] = '시작하려면 첫 번째 역할을 만드세요.';

// DynamicCohortMembersTable component strings
$string['searchmembers'] = '회원 검색...';
$string['first'] = '처음';
$string['last'] = '마지막';
$string['columns'] = '열';
$string['city'] = '도시';
$string['country'] = '국가';
$string['showing'] = '표시';
$string['to'] = '부터';
$string['previous'] = '이전';
$string['next'] = '다음';
$string['failedtofetchmembers'] = '회원을 불러오지 못했습니다';
$string['anerroroccurred'] = '오류가 발생했습니다';
$string['unknown'] = '알 수 없음';
$string['errordeletingmembers'] = '회원 삭제 중 오류가 발생했습니다';

// CohortEnrolInstancesSearchModal component strings
$string['searchcourses'] = '코스 검색';
$string['searchcoursesplaceholder'] = '코스 검색...';
$string['searching'] = '검색 중...';
$string['nocoursesfound'] = '코스를 찾을 수 없음';
$string['selectedcourses'] = '선택된 코스';
$string['coursealreadyselected'] = '이미 선택된 코스';
$string['noselectedcourses'] = '선택된 코스 없음';
$string['role'] = '역할';
$string['group'] = '그룹';
$string['remove'] = '제거';
$string['enroled'] = '등록됨';
$string['groupmembers'] = '그룹 회원';
$string['enrolinstanceadded'] = '등록 인스턴스가 추가되었습니다';
$string['errorcreatingenrolinstances'] = '등록 인스턴스 생성 중 오류가 발생했습니다. 다시 시도해 주세요.';

// Cohort role assignments (tool_cohortroles integration) strings
$string['cohortroles'] = '코호트 역할';
$string['cohortroleassignments'] = '할당';
$string['cohortroleassignmentscount'] = '개 할당';
$string['loadingcohortroles'] = '역할 할당 로딩 중...';
$string['noroughassignmentsfound'] = '할당을 찾을 수 없음';
$string['noroughassignmentsfounddescription'] = '할당을 찾을 수 없습니다. 역할이 있는 사용자를 할당하여 계층 구조를 설정하세요.';
$string['createyourfirstassignment'] = '시작하려면 첫 번째 할당을 만드세요.';
$string['addroleassignment'] = '할당 추가';
$string['assigneduser'] = '사용자';
$string['assignedrole'] = '역할';
$string['assignedcohort'] = '코호트';
$string['assignedtime'] = '할당 시간';
$string['deleteassignment'] = '할당 삭제';
$string['deleteassignmentconfirmation'] = '이 할당을 삭제하시겠습니까? 다음 동기화 시 제거됩니다.';
$string['cohortroleassignmentcreated'] = '할당이 생성되었습니다. 다음 동기화 후 적용됩니다.';
$string['cohortroleassignmentdeleted'] = '할당이 삭제되었습니다.';
$string['cohortroleassignmentexists'] = '이 할당이 이미 존재합니다.';
$string['backgroundsyncnote'] = '할당은 예약된 동기화 작업 실행 후 적용됩니다.';
$string['synclastsync'] = '마지막';
$string['syncnextsync'] = '다음';
$string['syncnever'] = '없음';
$string['selectuser'] = '사용자 선택';
$string['searchusers'] = '사용자 검색...';
$string['selectrole'] = '역할 선택';
$string['nousersfound'] = '사용자를 찾을 수 없음';
$string['assignroletouser'] = '역할 할당';
$string['userrolecohortinfo'] = '모든 회원에게 사용자 컨텍스트 역할을 할당합니다.';
$string['assignedon'] = '할당 날짜';
$string['deletethisassignment'] = '이 할당 삭제';
$string['assigneduserscount'] = '할당 수';
$string['perpage'] = '페이지 당';
$string['formvalidationerror'] = '양식의 오류를 수정해 주세요.';

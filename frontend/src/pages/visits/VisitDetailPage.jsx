import { useQuery } from "@tanstack/react-query";
import { Link, useParams } from "react-router-dom";

import apiClient from "../../api/client";
import ErrorMessage from "../../components/ErrorMessage";
import LoadingMessage from "../../components/LoadingMessage";

const statusLabels = {
  registered: "Đã tiếp nhận",
  awaiting_payment: "Chờ thu tiền",
  partial_paid: "Đã thu một phần",
  paid: "Đã thu đủ",
  collecting_documents: "Đang bổ sung hồ sơ",
  reviewing: "Đang kiểm tra",
  doctor_ready: "Sẵn sàng khám",
  completed: "Hoàn tất",
  cancelled: "Đã hủy",
};

const priorityLabels = {
  elderly: "Người cao tuổi",
  weak: "Bệnh nhân yếu",
  emergency: "Cấp cứu",
  other: "Khác",
};

export default function VisitDetailPage() {
  const { visitId } = useParams();

  const visitQuery = useQuery({
    queryKey: ["visit", visitId],
    queryFn: async () => {
      const response = await apiClient.get(`/visits/${visitId}`);
      return response.data;
    },
  });

  if (visitQuery.isLoading) {
    return <LoadingMessage text="Đang tải buổi khám..." />;
  }

  if (visitQuery.isError) {
    return <ErrorMessage error={visitQuery.error} />;
  }

  const visit = visitQuery.data;

  return (
    <section>
      <div className="page-header">
        <div>
          <h1>Buổi khám #{visit.queue_number || "—"}</h1>
          <p>{visit.visit_code}</p>
        </div>

        <div className="header-actions">
          <Link
            to={`/patients/${visit.patient_id}`}
            className="button button-secondary"
          >
            Xem bệnh nhân
          </Link>

          <Link to="/visits" className="button button-secondary">
            Danh sách buổi khám
          </Link>
        </div>
      </div>

      {visit.priority_flag && (
        <div className="priority-banner">
          <strong>Bệnh nhân ưu tiên</strong>

          <span>
            {priorityLabels[visit.priority_type] ||
              visit.priority_type ||
              "Chưa xác định"}
          </span>

          {visit.priority_note && <span>{visit.priority_note}</span>}
        </div>
      )}

      <div className="details-grid">
        <div className="card">
          <h2>Thông tin bệnh nhân</h2>

          <dl className="detail-list">
            <dt>Họ tên</dt>
            <dd>{visit.patient?.full_name || "—"}</dd>

            <dt>Mã bệnh nhân</dt>
            <dd>{visit.patient?.patient_code || "—"}</dd>

            <dt>Ngày sinh</dt>
            <dd>{visit.patient?.date_of_birth || "—"}</dd>

            <dt>Điện thoại</dt>
            <dd>{visit.patient?.phone || "—"}</dd>
          </dl>
        </div>

        <div className="card">
          <h2>Thông tin buổi khám</h2>

          <dl className="detail-list">
            <dt>Ngày khám</dt>
            <dd>{visit.visit_date}</dd>

            <dt>Số thứ tự</dt>
            <dd>{visit.queue_number || "—"}</dd>

            <dt>Bác sĩ khám</dt>
            <dd>{visit.doctor?.full_name || "—"}</dd>

            <dt>Loại khám</dt>
            <dd>
              {visit.visit_type === "first_visit"
                ? "Khám lần đầu"
                : "Tái khám"}
            </dd>

            <dt>Trạng thái</dt>
            <dd>
              {statusLabels[visit.status] || visit.status}
            </dd>

            <dt>Khám thay</dt>
            <dd>{visit.is_covering_doctor ? "Có" : "Không"}</dd>
          </dl>
        </div>
      </div>

      <div className="card">
        <h2>Ghi chú từ quầy</h2>

        <p className="pre-wrap">
          {visit.cashier_note || "Chưa có ghi chú."}
        </p>
      </div>

      <div className="card">
        <h2>Các bước tiếp theo</h2>

        <div className="action-grid">
          <Link
            to={`/visits/${visit.id}/services`}
            className="action-card"
          >
            <strong>Chọn dịch vụ và xét nghiệm</strong>
            <span>Thêm phí khám và các mục xét nghiệm.</span>
          </Link>

          <Link
            to={`/visits/${visit.id}/documents`}
            className="action-card"
          >
            <strong>Tài liệu hồ sơ</strong>
            <span>Upload phiếu xét nghiệm và giấy tờ.</span>
          </Link>
        </div>
      </div>
    </section>
  );
}

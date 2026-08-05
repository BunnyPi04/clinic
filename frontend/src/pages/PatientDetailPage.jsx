import { Link, useParams } from "react-router-dom";
import { useQuery } from "@tanstack/react-query";
import apiClient from "../api/client";
import ErrorMessage from "../components/ErrorMessage";
import LoadingMessage from "../components/LoadingMessage";

export default function PatientDetailPage() {
  const { patientId } = useParams();

  const patientQuery = useQuery({
    queryKey: ["patient", patientId],
    queryFn: async () => {
      const response = await apiClient.get(`/patients/${patientId}`);
      return response.data;
    },
  });

  if (patientQuery.isLoading) {
    return <LoadingMessage />;
  }

  if (patientQuery.isError) {
    return <ErrorMessage error={patientQuery.error} />;
  }

  const patient = patientQuery.data;
  const visits = patient.visits || [];

  return (
    <section>
      <div className="page-header">
        <div>
          <h1>{patient.full_name}</h1>
          <p>{patient.patient_code}</p>
        </div>

        <Link
          to={`/visits/create?patient_id=${patient.id}`}
          className="button button-primary"
        >
          Tạo buổi khám
        </Link>
      </div>

      <div className="details-grid">
        <div className="card">
          <h2>Thông tin bệnh nhân</h2>

          <dl className="detail-list">
            <dt>Ngày sinh</dt>
            <dd>{patient.date_of_birth || "—"}</dd>

            <dt>Giới tính</dt>
            <dd>{patient.gender || "—"}</dd>

            <dt>Số điện thoại</dt>
            <dd>{patient.phone || "—"}</dd>

            <dt>Zalo</dt>
            <dd>{patient.zalo_phone || "—"}</dd>

            <dt>Mã sổ giấy</dt>
            <dd>{patient.paper_book_code || "—"}</dd>

            <dt>Địa chỉ</dt>
            <dd>
              {[
                patient.house_number,
                patient.street,
                patient.district,
                patient.city,
              ]
                .filter(Boolean)
                .join(", ") || "—"}
            </dd>
          </dl>
        </div>

        <div className="card">
          <h2>Theo dõi</h2>

          <dl className="detail-list">
            <dt>Bác sĩ chính</dt>
            <dd>{patient.primary_doctor?.full_name || "—"}</dd>

            <dt>Nguồn bệnh nhân</dt>
            <dd>{patient.patient_source?.name || "—"}</dd>

            <dt>Liên hệ khẩn cấp</dt>
            <dd>
              {patient.emergency_contact_name || "—"}
              {patient.emergency_contact_phone
                ? ` – ${patient.emergency_contact_phone}`
                : ""}
            </dd>

            <dt>Ghi chú</dt>
            <dd>{patient.notes || "—"}</dd>
          </dl>
        </div>
      </div>

      <div className="table-card">
        <div className="card-title-row">
          <h2>Lịch sử buổi khám</h2>
        </div>

        <table>
          <thead>
            <tr>
              <th>Ngày khám</th>
              <th>Mã buổi khám</th>
              <th>Bác sĩ</th>
              <th>Trạng thái</th>
              <th>Ưu tiên</th>
              <th></th>
            </tr>
          </thead>

          <tbody>
            {visits.map((visit) => (
              <tr key={visit.id}>
                <td>{visit.visit_date}</td>
                <td>{visit.visit_code}</td>
                <td>{visit.doctor?.full_name || "—"}</td>
                <td>{visit.status}</td>
                <td>{visit.priority_flag ? "Có" : "Không"}</td>
                <td>
                  <Link to={`/visits/${visit.id}`}>Xem</Link>
                </td>
              </tr>
            ))}

            {visits.length === 0 && (
              <tr>
                <td colSpan="5" className="empty-cell">
                  Bệnh nhân chưa có buổi khám.
                </td>
              </tr>
            )}
          </tbody>
        </table>
      </div>
    </section>
  );
}

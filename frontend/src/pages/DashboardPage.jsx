import { useQuery } from "@tanstack/react-query";
import apiClient from "../api/client";
import ErrorMessage from "../components/ErrorMessage";
import LoadingMessage from "../components/LoadingMessage";

export default function DashboardPage() {
  const healthQuery = useQuery({
    queryKey: ["health"],
    queryFn: async () => {
      const response = await apiClient.get("/health");
      return response.data;
    },
  });

  return (
    <section>
      <div className="page-header">
        <div>
          <h1>Tổng quan</h1>
          <p>Hệ thống quản lý hồ sơ phòng khám.</p>
        </div>
      </div>

      {healthQuery.isLoading && <LoadingMessage />}

      {healthQuery.isError && <ErrorMessage error={healthQuery.error} />}

      {healthQuery.data && (
        <div className="card">
          <h2>Trạng thái backend</h2>
          <p>
            Laravel API:{" "}
            <strong>{healthQuery.data.ok ? "Đang hoạt động" : "Có lỗi"}</strong>
          </p>
          <p>Ứng dụng: {healthQuery.data.app}</p>
        </div>
      )}
    </section>
  );
}
